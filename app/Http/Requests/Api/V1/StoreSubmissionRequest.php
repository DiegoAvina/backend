<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Middleware\VerifyServiceKey;
use App\Services\Leads\SubmissionContext;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\Attributes\FailOnUnknownFields;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Base de los formularios comerciales. Replica las reglas de los esquemas
 * Zod del frontend (`frontend/src/features/leads/schemas.ts`); esta validación
 * es la que manda.
 *
 * Antes de validar se limpia la entrada: se eliminan etiquetas HTML y
 * caracteres de control, el correo se pasa a minúsculas y el teléfono se
 * normaliza a E.164 (+52 y 10 dígitos). Campos no previstos se rechazan.
 */
#[FailOnUnknownFields]
abstract class StoreSubmissionRequest extends FormRequest
{
    public const PHONE_PATTERN = '/^\+52\d{10}$/';

    public const IDEMPOTENCY_KEY_PATTERN = '/^[A-Za-z0-9_-]{16,100}$/';

    /**
     * El acceso lo controla el middleware `service.key`.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas propias de cada formulario.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    abstract protected function submissionRules(): array;

    /**
     * Atributos del modelo de la solicitud, a partir de los datos validados.
     *
     * @return array<string, mixed>
     */
    abstract public function submissionAttributes(): array;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'string', 'max:160', 'email:rfc'],
            'phone' => ['nullable', 'string', 'regex:'.self::PHONE_PATTERN],
            'company' => ['nullable', 'string', 'max:160'],
            'consent' => ['required', 'accepted'],
            'website' => ['prohibited'],
            'utm' => ['nullable', 'array:source,medium,campaign,term,content'],
            'utm.*' => ['nullable', 'string', 'max:150'],
            ...$this->submissionRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Ingresa un teléfono de 10 dígitos.',
            'website.prohibited' => 'No fue posible procesar la solicitud.',
        ];
    }

    /**
     * Valida el encabezado `Idempotency-Key` cuando se envía.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $key = $this->header('Idempotency-Key');

                if ($key !== null && preg_match(self::IDEMPOTENCY_KEY_PATTERN, $key) !== 1) {
                    $validator->errors()->add('idempotency_key', 'La llave de idempotencia no es válida.');
                }
            },
        ];
    }

    /**
     * Datos del prospecto.
     *
     * @return array{name: string, email: string, phone: ?string, company: ?string, role: ?string}
     */
    public function leadData(): array
    {
        return [
            'name' => (string) $this->validated('name'),
            'email' => (string) $this->validated('email'),
            'phone' => $this->validated('phone'),
            'company' => $this->validated('company'),
            'role' => $this->validated('role'),
        ];
    }

    public function submissionContext(): SubmissionContext
    {
        /** @var array<string, string>|null $utm */
        $utm = $this->validated('utm');

        return new SubmissionContext(
            ipHash: SubmissionContext::hashIp($this->attributes->get(VerifyServiceKey::CLIENT_IP)),
            userAgent: $this->attributes->get(VerifyServiceKey::CLIENT_USER_AGENT),
            idempotencyKey: $this->header('Idempotency-Key'),
            utm: $utm === null ? null : array_filter($utm, fn (mixed $value): bool => $value !== null),
        );
    }

    protected function prepareForValidation(): void
    {
        $input = $this->sanitize($this->all());

        if (is_string($input['email'] ?? null)) {
            $input['email'] = mb_strtolower($input['email']);
        }

        if (is_string($input['phone'] ?? null)) {
            $input['phone'] = self::normalizePhone($input['phone']);
        }

        $this->replace($input);
    }

    /**
     * Normaliza un teléfono mexicano a E.164. Si no tiene un formato
     * reconocible se devuelve tal cual para que la validación lo rechace.
     */
    public static function normalizePhone(string $phone): ?string
    {
        $compact = (string) preg_replace('/[\s().-]/', '', $phone);

        if ($compact === '') {
            return null;
        }

        if (preg_match('/^(?:\+?52)?(\d{10})$/', $compact, $matches) === 1) {
            return '+52'.$matches[1];
        }

        return $phone;
    }

    /**
     * Elimina etiquetas HTML y caracteres de control (conserva saltos de línea).
     * Un texto que queda vacío se convierte en null.
     *
     * @param  array<mixed>  $input
     * @return array<mixed>
     */
    private function sanitize(array $input): array
    {
        foreach ($input as $key => $value) {
            if (is_array($value)) {
                $input[$key] = $this->sanitize($value);
            } elseif (is_string($value)) {
                $clean = strip_tags($value);
                $clean = (string) preg_replace('/[^\P{C}\t\n\r]/u', '', $clean);
                $clean = trim($clean);
                $input[$key] = $clean === '' ? null : $clean;
            }
        }

        return $input;
    }
}
