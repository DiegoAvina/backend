<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Catálogo de claves de configuración del sitio.
 *
 * Define qué claves existen, su tipo, su grupo y si son públicas. Solo las
 * marcadas como públicas llegan a `GET /api/v1/settings/public`. La tabla
 * `settings` guarda únicamente los valores; un valor no definido queda nulo
 * (no se inventan datos comerciales).
 */
enum SettingKey: string implements HasLabel
{
    case BrandName = 'brand_name';
    case BrandLogo = 'brand_logo';
    case BrandDescription = 'brand_description';
    case ContactEmail = 'contact_email';
    case ContactPhone = 'contact_phone';
    case WhatsappNumber = 'whatsapp_number';
    case ContactCity = 'contact_city';
    case Address = 'address';
    case SocialLinkedin = 'social_linkedin';
    case SocialFacebook = 'social_facebook';
    case SocialInstagram = 'social_instagram';
    case SocialYoutube = 'social_youtube';
    case SeoTitle = 'seo_title';
    case SeoDescription = 'seo_description';
    case SeoImage = 'seo_image';
    case FooterText = 'footer_text';
    case LegalName = 'legal_name';
    case LegalAddress = 'legal_address';
    case PrivacyEmail = 'privacy_email';
    case PrivacyLastUpdated = 'privacy_last_updated';
    case NotificationRecipients = 'notification_recipients';

    public function label(): string
    {
        return match ($this) {
            self::BrandName => 'Nombre de marca',
            self::BrandLogo => 'Logotipo',
            self::BrandDescription => 'Descripción de la empresa',
            self::ContactEmail => 'Correo comercial',
            self::ContactPhone => 'Teléfono',
            self::WhatsappNumber => 'WhatsApp',
            self::ContactCity => 'Ciudad',
            self::Address => 'Dirección',
            self::SocialLinkedin => 'LinkedIn',
            self::SocialFacebook => 'Facebook',
            self::SocialInstagram => 'Instagram',
            self::SocialYoutube => 'YouTube',
            self::SeoTitle => 'Título SEO predeterminado',
            self::SeoDescription => 'Descripción SEO predeterminada',
            self::SeoImage => 'Imagen para redes sociales (OG)',
            self::FooterText => 'Texto del pie de página',
            self::LegalName => 'Razón social (aviso de privacidad)',
            self::LegalAddress => 'Domicilio del responsable',
            self::PrivacyEmail => 'Correo para derechos ARCO',
            self::PrivacyLastUpdated => 'Última actualización del aviso',
            self::NotificationRecipients => 'Destinatarios de notificaciones comerciales',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    /**
     * Tipo de dato: string, text, email, phone, whatsapp, url, image o emails.
     */
    public function type(): string
    {
        return match ($this) {
            self::BrandDescription, self::Address, self::SeoDescription, self::FooterText, self::LegalAddress => 'text',
            self::ContactEmail, self::PrivacyEmail => 'email',
            self::ContactPhone => 'phone',
            self::WhatsappNumber => 'whatsapp',
            self::SocialLinkedin, self::SocialFacebook, self::SocialInstagram, self::SocialYoutube => 'url',
            self::BrandLogo, self::SeoImage => 'image',
            self::NotificationRecipients => 'emails',
            default => 'string',
        };
    }

    public function group(): string
    {
        return match ($this) {
            self::BrandName, self::BrandLogo, self::BrandDescription => 'brand',
            self::ContactEmail, self::ContactPhone, self::WhatsappNumber, self::ContactCity, self::Address => 'contact',
            self::SocialLinkedin, self::SocialFacebook, self::SocialInstagram, self::SocialYoutube => 'social',
            self::SeoTitle, self::SeoDescription, self::SeoImage => 'seo',
            self::FooterText => 'footer',
            self::LegalName, self::LegalAddress, self::PrivacyEmail, self::PrivacyLastUpdated => 'legal',
            self::NotificationRecipients => 'notifications',
        };
    }

    /**
     * Solo estas claves se publican en la API. Los destinatarios internos de
     * notificaciones nunca son públicos.
     */
    public function isPublic(): bool
    {
        return $this !== self::NotificationRecipients;
    }

    /**
     * Reglas de validación del valor (además de ser opcional).
     *
     * @return list<string>
     */
    public function rules(): array
    {
        return match ($this->type()) {
            'email' => ['email:rfc', 'max:160'],
            'url' => ['url:https', 'max:255'],
            'phone' => ['regex:/^\+?[0-9 ()-]{10,20}$/', 'max:20'],
            'whatsapp' => ['regex:/^[0-9]{10,15}$/'],
            'text' => ['max:1000'],
            'image' => ['max:255'],
            'emails' => ['max:1000'],
            default => ['max:160'],
        };
    }

    public static function groupLabel(string $group): string
    {
        return match ($group) {
            'brand' => 'Marca',
            'contact' => 'Contacto',
            'social' => 'Redes sociales',
            'seo' => 'SEO global',
            'footer' => 'Pie de página',
            'legal' => 'Aviso de privacidad',
            'notifications' => 'Notificaciones (privado)',
            default => $group,
        };
    }
}
