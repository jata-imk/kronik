<?php

namespace App\Enums;

enum ActivityEvent: string
{
    case Login = 'login';
    case TwoFactorCompleted = 'login.2fa_completed';
    case UserProfileUpdated = 'user.profile.updated';
    case CompanyUpdated = 'empresa.updated';
    case BranchCreated = 'sucursal.created';
    case BranchUpdated = 'sucursal.updated';
    case BranchDeactivated = 'sucursal.deactivated';
    case ClientCreated = 'cliente.created';
    case ClientUpdated = 'cliente.updated';
    case ClientDeleted = 'cliente.deleted';
    case ClientTransferred = 'cliente.sucursal.transferred';
    case ClientKycUpdated = 'cliente.kyc.updated';
    case ClientReferenceCreated = 'cliente.referencia.created';
    case ClientReferenceUpdated = 'cliente.referencia.updated';
    case ClientReferenceDeleted = 'cliente.referencia.deleted';
    case ClientLinkCreated = 'cliente.vinculo.created';
    case ClientLinkDeleted = 'cliente.vinculo.deleted';
    case ClientGuaranteeCreated = 'cliente.garantia.created';
    case ClientGuaranteeUpdated = 'cliente.garantia.updated';
    case ClientGuaranteeDeleted = 'cliente.garantia.deleted';
    case ClientDocumentReceived = 'cliente.documento.received';
    case ClientDocumentStatusUpdated = 'cliente.documento.status_updated';
    case ClientDocumentDownloaded = 'cliente.documento.downloaded';
    case ClientSicConsentCreated = 'cliente.consentimiento_sic.created';
    case ClientSicConsentRevoked = 'cliente.consentimiento_sic.revoked';
    case ClientSicConsentEvidenceDownloaded = 'cliente.consentimiento_sic.evidence_downloaded';
    case ClientSicFicoScoreV2Queried = 'cliente.sic.fico_score_v2.queried';
    case ClientSicFintechScoreQueried = 'cliente.sic.fintech_score.queried';
    case ClientSicCreditReportFicoQueried = 'cliente.sic.credit_report_fico.queried';
    case CreditProductCreated = 'producto_crediticio.created';
    case CreditProductUpdated = 'producto_crediticio.updated';
    case CreditProductVersioned = 'producto_crediticio.versioned';
    case CreditProductActivated = 'producto_crediticio.activated';
    case CreditProductRetired = 'producto_crediticio.retired';
    case DocumentTemplateCreated = 'documento_plantilla.created';
    case DocumentTemplateUpdated = 'documento_plantilla.updated';
    case DocumentTemplateVersioned = 'documento_plantilla.versioned';
    case DocumentTemplateActivated = 'documento_plantilla.activated';
    case DocumentTemplateRetired = 'documento_plantilla.retired';
    case DocumentGenerationRequested = 'documento.generacion.requested';
    case DocumentGenerated = 'documento.generated';
    case DocumentGenerationFailed = 'documento.generacion.failed';
    case DocumentViewed = 'documento.viewed';
    case DocumentDownloaded = 'documento.downloaded';
    case ClientDocumentViewed = 'cliente.documento.viewed';
    case ClientSicConsentEvidenceViewed = 'cliente.consentimiento_sic.evidence_viewed';

    public function label(): string
    {
        return match ($this) {
            self::Login => 'Inicio de sesión',
            self::TwoFactorCompleted => 'Autenticación de dos factores completada',
            self::UserProfileUpdated => 'Perfil de usuario actualizado',
            self::CompanyUpdated => 'Empresa actualizada',
            self::BranchCreated => 'Sucursal creada',
            self::BranchUpdated => 'Sucursal actualizada',
            self::BranchDeactivated => 'Sucursal desactivada',
            self::ClientCreated => 'Cliente creado',
            self::ClientUpdated => 'Cliente actualizado',
            self::ClientDeleted => 'Cliente eliminado',
            self::ClientTransferred => 'Cliente trasladado de sucursal',
            self::ClientKycUpdated => 'Perfil KYC actualizado',
            self::ClientReferenceCreated => 'Referencia de cliente creada',
            self::ClientReferenceUpdated => 'Referencia de cliente actualizada',
            self::ClientReferenceDeleted => 'Referencia de cliente eliminada',
            self::ClientLinkCreated => 'Persona vinculada al expediente',
            self::ClientLinkDeleted => 'Persona desvinculada del expediente',
            self::ClientGuaranteeCreated => 'Garantía creada',
            self::ClientGuaranteeUpdated => 'Garantía actualizada',
            self::ClientGuaranteeDeleted => 'Garantía eliminada',
            self::ClientDocumentReceived => 'Documento de cliente recibido',
            self::ClientDocumentStatusUpdated => 'Estado documental actualizado',
            self::ClientDocumentDownloaded => 'Documento de cliente descargado',
            self::ClientSicConsentCreated => 'Consentimiento SIC registrado',
            self::ClientSicConsentRevoked => 'Consentimiento SIC revocado',
            self::ClientSicConsentEvidenceDownloaded => 'Evidencia de consentimiento SIC descargada',
            self::ClientSicFicoScoreV2Queried => 'Consulta SIC FICO Score v2',
            self::ClientSicFintechScoreQueried => 'Consulta SIC Fintech Score',
            self::ClientSicCreditReportFicoQueried => 'Consulta de reporte de crédito con FICO Score',
            self::CreditProductCreated => 'Producto crediticio creado',
            self::CreditProductUpdated => 'Producto crediticio actualizado',
            self::CreditProductVersioned => 'Producto crediticio versionado',
            self::CreditProductActivated => 'Versión de producto activada',
            self::CreditProductRetired => 'Versión de producto retirada',
            self::DocumentTemplateCreated => 'Plantilla documental creada',
            self::DocumentTemplateUpdated => 'Borrador de plantilla actualizado',
            self::DocumentTemplateVersioned => 'Plantilla documental versionada',
            self::DocumentTemplateActivated => 'Versión de plantilla activada',
            self::DocumentTemplateRetired => 'Versión de plantilla retirada',
            self::DocumentGenerationRequested => 'Generación de documento solicitada',
            self::DocumentGenerated => 'Documento generado',
            self::DocumentGenerationFailed => 'Generación de documento fallida',
            self::DocumentViewed => 'Documento visualizado',
            self::DocumentDownloaded => 'Documento descargado',
            self::ClientDocumentViewed => 'Documento de cliente visualizado',
            self::ClientSicConsentEvidenceViewed => 'Evidencia SIC visualizada',
        };
    }

    public function severity(): string
    {
        return match ($this) {
            self::BranchCreated,
            self::ClientCreated,
            self::ClientReferenceCreated,
            self::ClientLinkCreated,
            self::ClientGuaranteeCreated,
            self::ClientDocumentReceived,
            self::ClientSicConsentCreated,
            self::DocumentTemplateCreated,
            self::DocumentTemplateActivated,
            self::DocumentGenerated => 'success',
            self::BranchDeactivated,
            self::ClientDeleted,
            self::ClientReferenceDeleted,
            self::ClientLinkDeleted,
            self::ClientGuaranteeDeleted,
            self::ClientSicConsentRevoked,
            self::DocumentGenerationFailed => 'danger',
            self::Login,
            self::TwoFactorCompleted => 'secondary',
            default => 'info',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Login => 'pi-sign-in',
            self::TwoFactorCompleted => 'pi-shield',
            self::UserProfileUpdated => 'pi-user-edit',
            self::CompanyUpdated => 'pi-building',
            self::BranchCreated => 'pi-plus',
            self::BranchUpdated => 'pi-pencil',
            self::BranchDeactivated => 'pi-ban',
            self::ClientCreated => 'pi-user-plus',
            self::ClientDeleted => 'pi-user-minus',
            self::ClientTransferred => 'pi-arrow-right-arrow-left',
            self::ClientDocumentReceived => 'pi-upload',
            self::ClientDocumentDownloaded,
            self::ClientSicConsentEvidenceDownloaded => 'pi-download',
            self::ClientSicFicoScoreV2Queried,
            self::ClientSicFintechScoreQueried,
            self::ClientSicCreditReportFicoQueried => 'pi-search',
            self::CreditProductCreated => 'pi-plus',
            self::CreditProductUpdated => 'pi-pencil',
            self::CreditProductVersioned => 'pi-copy',
            self::CreditProductActivated => 'pi-check-circle',
            self::CreditProductRetired => 'pi-ban',
            self::DocumentTemplateCreated => 'pi-file-plus',
            self::DocumentTemplateUpdated => 'pi-pencil',
            self::DocumentTemplateVersioned => 'pi-copy',
            self::DocumentTemplateActivated => 'pi-check-circle',
            self::DocumentTemplateRetired => 'pi-ban',
            self::DocumentGenerationRequested => 'pi-clock',
            self::DocumentGenerated => 'pi-file-pdf',
            self::DocumentGenerationFailed => 'pi-exclamation-triangle',
            self::DocumentViewed,
            self::ClientDocumentViewed,
            self::ClientSicConsentEvidenceViewed => 'pi-eye',
            self::DocumentDownloaded => 'pi-download',
            default => 'pi-circle',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $event) => ['value' => $event->value, 'label' => $event->label()],
            self::cases(),
        );
    }
}
