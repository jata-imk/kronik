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
            self::ClientSicConsentCreated => 'success',
            self::BranchDeactivated,
            self::ClientDeleted,
            self::ClientReferenceDeleted,
            self::ClientLinkDeleted,
            self::ClientGuaranteeDeleted,
            self::ClientSicConsentRevoked => 'danger',
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
            self::ClientDocumentReceived => 'pi-upload',
            self::ClientDocumentDownloaded,
            self::ClientSicConsentEvidenceDownloaded => 'pi-download',
            self::ClientSicFicoScoreV2Queried,
            self::ClientSicFintechScoreQueried,
            self::ClientSicCreditReportFicoQueried => 'pi-search',
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
