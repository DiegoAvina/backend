<?php

namespace App\Observers;

use App\Enums\ArticleStatus;
use App\Jobs\RevalidateFrontendContent;
use App\Models\Article;
use App\Models\Category;
use App\Models\Industry;
use App\Models\Product;
use App\Models\Service;
use App\Models\Solution;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Contenido público: auditoría, revalidación del frontend y limpieza de
 * imágenes reemplazadas o eliminadas.
 */
class ContentObserver
{
    /** Columnas con rutas de imágenes en el disco `public`. */
    private const IMAGE_COLUMNS = ['image', 'cover_image'];

    public function __construct(private AuditLogger $audit) {}

    public function created(Model $model): void
    {
        $this->audit->log('created', $model, $this->title($model), null, $model->attributesToArray());
        $this->revalidate($model);
    }

    public function updated(Model $model): void
    {
        [$old, $new] = $this->audit->diff($model);

        if ($new === []) {
            return;
        }

        $event = match ($this->publicationChange($model)) {
            true => 'published',
            false => 'unpublished',
            null => 'updated',
        };

        $this->audit->log($event, $model, $this->title($model), $old, $new);
        $this->deleteReplacedImages($model);
        $this->revalidate($model);
    }

    public function deleted(Model $model): void
    {
        $this->audit->log('deleted', $model, $this->title($model));
        $this->revalidate($model);

        $softDeleted = method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting();
        if (! $softDeleted) {
            $this->deleteImages($model);
        }
    }

    public function restored(Model $model): void
    {
        $this->audit->log('restored', $model, $this->title($model));
        $this->revalidate($model);
    }

    /**
     * true = se publicó, false = se despublicó, null = sin cambio de publicación.
     */
    private function publicationChange(Model $model): ?bool
    {
        if ($model instanceof Article) {
            if (! $model->wasChanged('status')) {
                return null;
            }

            $wasPublished = $model->getOriginal('status') === ArticleStatus::Published;
            $isPublished = $model->status === ArticleStatus::Published;

            return $wasPublished === $isPublished ? null : $isPublished;
        }

        if (! $model->wasChanged('is_published')) {
            return null;
        }

        return (bool) $model->getAttribute('is_published');
    }

    private function revalidate(Model $model): void
    {
        $tags = self::tagsFor($model);

        if ($tags !== []) {
            RevalidateFrontendContent::dispatch($tags);
        }
    }

    /**
     * Etiquetas de caché del frontend afectadas por un cambio en el modelo.
     * Productos y servicios también aparecen en soluciones e industrias.
     *
     * @return list<string>
     */
    public static function tagsFor(Model $model): array
    {
        return match (true) {
            $model instanceof Product => ['products', 'solutions', 'industries'],
            $model instanceof Service => ['services', 'solutions', 'industries'],
            $model instanceof Solution => ['solutions'],
            $model instanceof Industry => ['industries'],
            $model instanceof Category => ['categories', 'products', 'articles'],
            $model instanceof Article => ['articles'],
            default => [],
        };
    }

    private function deleteReplacedImages(Model $model): void
    {
        foreach (self::IMAGE_COLUMNS as $column) {
            $previous = $model->getOriginal($column);

            if ($model->wasChanged($column) && is_string($previous) && $previous !== '') {
                Storage::disk('public')->delete($previous);
            }
        }
    }

    private function deleteImages(Model $model): void
    {
        foreach (self::IMAGE_COLUMNS as $column) {
            $path = $model->getAttribute($column);

            if (is_string($path) && $path !== '') {
                Storage::disk('public')->delete($path);
            }
        }
    }

    private function title(Model $model): string
    {
        $attributes = $model->getAttributes();

        return (string) ($attributes['name'] ?? $attributes['title'] ?? $model->getKey());
    }
}
