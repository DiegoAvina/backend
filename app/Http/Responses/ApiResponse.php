<?php

namespace App\Http\Responses;

use App\Http\Resources\V1\SubmissionResource;
use App\Services\Leads\SubmissionResult;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Formato único de respuesta de la API v1.
 *
 * Éxito:  { "success": true,  "message": "...", "data": ..., "meta"?: {...} }
 * Error:  { "success": false, "message": "...", "errors": {...} }
 */
final class ApiResponse
{
    public const GENERIC_ERROR = 'No fue posible procesar la solicitud.';

    /**
     * @param  array<string, mixed>  $meta
     * @param  array<string, string>  $headers
     */
    public static function success(
        mixed $data = null,
        string $message = 'Operación completada.',
        int $status = 200,
        array $meta = [],
        array $headers = [],
    ): JsonResponse {
        if ($data instanceof JsonResource) {
            $data = $data->resolve(request());
        }

        $payload = ['success' => true, 'message' => $message, 'data' => $data];

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return new JsonResponse($payload, $status, $headers);
    }

    /**
     * Lista paginada: los elementos van en `data` y la paginación en `meta`.
     */
    public static function paginated(ResourceCollection $collection, string $message = 'Operación completada.'): JsonResponse
    {
        /** @var LengthAwarePaginator<int, mixed> $paginator */
        $paginator = $collection->resource;

        return self::success($collection->resolve(request()), $message, meta: [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
        ]);
    }

    /**
     * Acuse de un formulario: 201 si se creó el registro; 200 con el encabezado
     * `Idempotent-Replayed` si era un reintento de una solicitud ya registrada.
     */
    public static function submission(SubmissionResult $result): JsonResponse
    {
        $resource = new SubmissionResource($result->record);

        return $result->created
            ? self::success($resource, 'Solicitud registrada.', 201)
            : self::success($resource, 'La solicitud ya había sido registrada.', 200, headers: ['Idempotent-Replayed' => 'true']);
    }

    /**
     * @param  array<string, list<string>>  $errors
     * @param  array<string, string>  $headers
     */
    public static function error(string $message, int $status, array $errors = [], array $headers = []): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'message' => $message,
            'errors' => (object) $errors,
        ], $status, $headers);
    }

    /**
     * Traduce cualquier excepción a una respuesta con el formato de la API.
     * Nunca incluye trazas; el detalle técnico solo se agrega con APP_DEBUG activo.
     */
    public static function fromException(Throwable $exception): JsonResponse
    {
        $response = match (true) {
            $exception instanceof ValidationException => self::error(
                'Los datos enviados no son válidos.',
                422,
                $exception->errors(),
            ),
            $exception instanceof ModelNotFoundException,
            $exception instanceof NotFoundHttpException => self::error('Recurso no encontrado.', 404),
            $exception instanceof MethodNotAllowedHttpException => self::error(
                'Método no permitido.',
                405,
                headers: $exception->getHeaders(),
            ),
            $exception instanceof ThrottleRequestsException => self::error(
                'Demasiadas solicitudes. Intenta de nuevo más tarde.',
                429,
                headers: $exception->getHeaders(),
            ),
            $exception instanceof AuthenticationException => self::error('No autenticado.', 401),
            $exception instanceof AuthorizationException => self::error('Acceso denegado.', 403),
            $exception instanceof HttpExceptionInterface => self::error(
                $exception->getStatusCode() < 500 ? self::GENERIC_ERROR : 'El servicio no está disponible.',
                $exception->getStatusCode(),
                headers: $exception->getHeaders(),
            ),
            default => self::error(self::GENERIC_ERROR, 500),
        };

        if (config('app.debug') && $response->getStatusCode() >= 500) {
            $payload = $response->getData(true);
            $payload['debug'] = ['exception' => $exception::class, 'detail' => $exception->getMessage()];
            $response->setData($payload);
        }

        return $response;
    }
}
