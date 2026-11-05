<?php

namespace App\Filament\Resources\Articles\Pages;

use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Support\EditContentRecord;

class EditArticle extends EditContentRecord
{
    protected static string $resource = ArticleResource::class;
}
