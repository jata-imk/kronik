<?php

namespace App\Enums;

enum ActivityEvent: string
{
    case TeamCreated = 'team.created';
    case TeamUpdated = 'team.updated';
    case TeamDeactivated = 'team.deactivated';
    case TeamReactivated = 'team.reactivated';
    case RoleCreated = 'role.created';
    case RoleUpdated = 'role.updated';
    case RoleDeleted = 'role.deleted';
    case RolePermissionsUpdated = 'role.permissions.updated';
    case PermissionCreated = 'permission.created';
    case Logout = 'logout';
    case LoginFailed = 'login.failed';
    case LoginThrottled = 'login.throttled';
    case LoginRejected = 'login.rejected';
    case TwoFactorEnabled = 'user.2fa.enabled';
    case TwoFactorConfirmed = 'user.2fa.confirmed';
    case TwoFactorDisabled = 'user.2fa.disabled';
    case RecoveryCodesRegenerated = 'user.2fa.recovery_codes_regenerated';
    case TwoFactorFailed = 'login.2fa_failed';
    case TwoFactorThrottled = 'login.2fa_throttled';
    case ApiTokenCreated = 'user.api_token.created';
    case ApiTokenUpdated = 'user.api_token.updated';
    case ApiTokenRevoked = 'user.api_token.revoked';
    case EmailVerified = 'user.email_verified';
    case ActivityExported = 'activity_log.exported';
    case CreditProductActivationScheduled = 'producto_crediticio.activation_scheduled';
    case SicRequested = 'sic.requested';
    case SicSending = 'sic.sending';
    case SicCancelled = 'sic.cancelled';
    case SicUncertain = 'sic.uncertain';
    case SicLateResponse = 'sic.late_response';
    case ActiveBranchChanged = 'user.active_branch_changed';
    case ActiveTeamChanged = 'user.active_team_changed';
    case OtherSessionsInvalidated = 'user.sessions.invalidated';
    case UserCreated = 'user.created';
    case UserUpdated = 'user.updated';
    case UserDeactivated = 'user.deactivated';
    case UserReactivated = 'user.reactivated';
    case TeamMemberAdded = 'team.member.added';
    case TeamMemberRemoved = 'team.member.removed';
    case TeamMemberLegacyRoleUpdated = 'team.member.legacy_role_updated';
    case UserTeamRolesUpdated = 'user.team_roles.updated';
    case InvitationSent = 'user.invitation.sent';
    case InvitationResent = 'user.invitation.resent';
    case InvitationFailed = 'user.invitation.failed';
    case InvitationThrottled = 'user.invitation.throttled';
    case UserActivated = 'user.activated';
    case PasswordRecovered = 'user.password.recovered';
    case PasswordChanged = 'user.password.changed';
    case TeamResponsibleTransferred = 'team.responsible_transferred';
    case SicAttemptReconciled = 'sic.attempt_reconciled';
    case Login = 'login';
    case ApplicationCreated = 'solicitud.created';
    case ApplicationUpdated = 'solicitud.updated';
    case ApplicationSubmitted = 'solicitud.submitted';
    case ApplicationAssigned = 'solicitud.assigned';
    case ApplicationReturned = 'solicitud.returned';
    case ApplicationRejected = 'solicitud.rejected';
    case ApplicationCancelled = 'solicitud.cancelled';
    case ApplicationAssessmentRecorded = 'solicitud.assessment_recorded';
    case OriginationPolicyCreated = 'originacion.politica_created';
    case ApplicationApproved = 'solicitud.approved';
    case ApplicationSignatureReceived = 'solicitud.signature_received';
    case ApplicationSignatureReviewed = 'solicitud.signature_reviewed';
    case CreditHistoricalCaptureCompleted = 'credito.historical_capture_completed';
    case CreditDisbursed = 'credito.disbursed';
    case CreditPaymentRecorded = 'credito.payment_recorded';
    case CreditPaymentReversed = 'credito.payment_reversed';
    case TwoFactorCompleted = 'login.2fa_completed';
    case UserProfileUpdated = 'user.profile.updated';
    case MenubarItemCreated = 'menubar_item.created';
    case MenubarItemUpdated = 'menubar_item.updated';
    case MenubarItemDeleted = 'menubar_item.deleted';
    case CompanyUpdated = 'empresa.updated';
    case CompanyOperationBlocked = 'empresa.operacion_bloqueada';
    case BranchCreated = 'sucursal.created';
    case BranchUpdated = 'sucursal.updated';
    case BranchFolioConfigurationUpdated = 'sucursal.folios.updated';
    case HistoricalFolioAssigned = 'folio.historical_assigned';
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
    case SicEvidenceViewed = 'cliente.sic.evidence_viewed';
    case ClientSicFintechScoreQueried = 'cliente.sic.fintech_score.queried';
    case ClientSicCreditReportFicoQueried = 'cliente.sic.credit_report_fico.queried';
    case CreditProductCreated = 'producto_crediticio.created';
    case CreditProductUpdated = 'producto_crediticio.updated';
    case CreditProductVersioned = 'producto_crediticio.versioned';
    case CreditProductActivated = 'producto_crediticio.activated';
    case CreditProductRetired = 'producto_crediticio.retired';
    case DocumentTemplateCreated = 'documento_plantilla.created';
    case DocumentResourceCreated = 'documento_recurso.created';
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
            self::TeamCreated => 'Equipo creado',
            self::TeamUpdated => 'Equipo actualizado',
            self::TeamDeactivated => 'Equipo desactivado',
            self::TeamReactivated => 'Equipo reactivado',
            self::RoleCreated => 'Rol creado',
            self::RoleUpdated => 'Rol actualizado',
            self::RoleDeleted => 'Rol eliminado',
            self::RolePermissionsUpdated => 'Permisos del rol actualizados',
            self::PermissionCreated => 'Permiso creado',
            self::Logout => 'Sesión cerrada',
            self::LoginFailed => 'Credenciales de acceso rechazadas',
            self::LoginThrottled => 'Acceso limitado por frecuencia',
            self::LoginRejected => 'Acceso rechazado por estado de cuenta',
            self::TwoFactorEnabled => 'Configuración de segundo factor iniciada',
            self::TwoFactorConfirmed => 'Segundo factor confirmado',
            self::TwoFactorDisabled => 'Segundo factor deshabilitado',
            self::RecoveryCodesRegenerated => 'Códigos de recuperación regenerados',
            self::TwoFactorFailed => 'Segundo factor rechazado',
            self::TwoFactorThrottled => 'Segundo factor limitado por frecuencia',
            self::ApiTokenCreated => 'Token API creado',
            self::ApiTokenUpdated => 'Capacidades del token API actualizadas',
            self::ApiTokenRevoked => 'Token API revocado',
            self::EmailVerified => 'Correo verificado',
            self::ActivityExported => 'Archivo CSV de actividad generado',
            self::CreditProductActivationScheduled => 'Activación de producto programada',
            self::SicRequested => 'Intento SIC solicitado',
            self::SicSending => 'Envío SIC iniciado',
            self::SicCancelled => 'Intento SIC cancelado antes del envío',
            self::SicUncertain => 'Intento SIC marcado incierto',
            self::SicLateResponse => 'Respuesta SIC tardía conservada',
            self::ActiveBranchChanged => 'Sucursal activa cambiada',
            self::ActiveTeamChanged => 'Equipo activo cambiado',
            self::OtherSessionsInvalidated => 'Otras sesiones invalidadas',
            self::UserCreated => 'Cuenta pendiente creada',
            self::UserUpdated => 'Cuenta actualizada',
            self::UserDeactivated => 'Cuenta desactivada',
            self::UserReactivated => 'Cuenta reactivada',
            self::TeamMemberAdded => 'Integrante agregado al equipo',
            self::TeamMemberRemoved => 'Integrante retirado del equipo',
            self::TeamMemberLegacyRoleUpdated => 'Rol genérico de membresía actualizado',
            self::UserTeamRolesUpdated => 'Roles del integrante actualizados',
            self::InvitationSent => 'Invitación aceptada por el transporte',
            self::InvitationResent => 'Reenvío de invitación aceptado por el transporte',
            self::InvitationFailed => 'Envío de invitación fallido',
            self::InvitationThrottled => 'Reenvío de invitación limitado por frecuencia',
            self::UserActivated => 'Cuenta activada con contraseña inicial',
            self::PasswordRecovered => 'Contraseña recuperada',
            self::PasswordChanged => 'Contraseña cambiada',
            self::TeamResponsibleTransferred => 'Responsable del equipo transferido',
            self::CreditPaymentRecorded => 'Pago QA registrado',
            self::CreditPaymentReversed => 'Pago QA revertido',
            self::ApplicationCreated => 'Solicitud creada',
            self::ApplicationUpdated => 'Borrador de solicitud actualizado',
            self::ApplicationSubmitted => 'Solicitud enviada a revisión',
            self::ApplicationAssigned => 'Responsable de solicitud asignado',
            self::ApplicationReturned => 'Solicitud devuelta para corrección',
            self::ApplicationRejected => 'Solicitud rechazada',
            self::ApplicationCancelled => 'Solicitud cancelada',
            self::ApplicationAssessmentRecorded => 'Revisión especializada registrada',
            self::OriginationPolicyCreated => 'Política de originación versionada',
            self::ApplicationApproved => 'Solicitud aprobada',
            self::ApplicationSignatureReceived => 'Copia firmada recibida',
            self::ApplicationSignatureReviewed => 'Firma revisada',
            self::CreditHistoricalCaptureCompleted => 'Captura histórica QA completada',
            self::CreditDisbursed => 'Desembolso QA registrado',
            self::Login => 'Inicio de sesión',
            self::TwoFactorCompleted => 'Autenticación de dos factores completada',
            self::UserProfileUpdated => 'Perfil de usuario actualizado',
            self::MenubarItemCreated => 'Elemento de menú creado',
            self::MenubarItemUpdated => 'Elemento de menú actualizado',
            self::MenubarItemDeleted => 'Elemento de menú eliminado',
            self::CompanyUpdated => 'Empresa actualizada',
            self::CompanyOperationBlocked => 'Operación bloqueada por el estado de la empresa',
            self::BranchCreated => 'Sucursal creada',
            self::BranchUpdated => 'Sucursal actualizada',
            self::BranchFolioConfigurationUpdated => 'Numeración de sucursal actualizada',
            self::HistoricalFolioAssigned => 'Folio asignado a registro histórico',
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
            self::SicEvidenceViewed => 'Resultado SIC visualizado',
            self::SicAttemptReconciled => 'Intento SIC conciliado',
            self::ClientSicFintechScoreQueried => 'Consulta SIC Fintech Score',
            self::ClientSicCreditReportFicoQueried => 'Consulta de reporte de crédito con FICO Score',
            self::CreditProductCreated => 'Producto crediticio creado',
            self::CreditProductUpdated => 'Producto crediticio actualizado',
            self::CreditProductVersioned => 'Producto crediticio versionado',
            self::CreditProductActivated => 'Versión de producto activada',
            self::CreditProductRetired => 'Versión de producto retirada',
            self::DocumentTemplateCreated => 'Plantilla documental creada',
            self::DocumentResourceCreated => 'Imagen documental incorporada',
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
            self::LoginFailed,
            self::LoginThrottled,
            self::LoginRejected,
            self::TwoFactorFailed,
            self::TwoFactorThrottled,
            self::SicCancelled,
            self::SicUncertain => 'warn',
            self::TeamCreated,
            self::RoleCreated,
            self::PermissionCreated,
            self::TwoFactorConfirmed,
            self::EmailVerified => 'success',
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
            self::Logout,
            self::TwoFactorCompleted => 'secondary',
            default => 'info',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Logout => 'pi-sign-out',
            self::LoginFailed,
            self::LoginThrottled,
            self::LoginRejected => 'pi-lock',
            self::TwoFactorEnabled,
            self::TwoFactorConfirmed,
            self::TwoFactorDisabled,
            self::TwoFactorFailed,
            self::TwoFactorThrottled,
            self::RecoveryCodesRegenerated => 'pi-shield',
            self::ApiTokenCreated,
            self::ApiTokenUpdated,
            self::ApiTokenRevoked => 'pi-key',
            self::ActivityExported => 'pi-download',
            self::CreditProductActivationScheduled => 'pi-clock',
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
            self::DocumentResourceCreated => 'pi-image',
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
