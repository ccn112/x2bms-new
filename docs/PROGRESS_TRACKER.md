# X2-BMS — PROGRESS TRACKER (Theo dõi tiến độ chính thức)

> **File này là NGUỒN THEO DÕI TIẾN ĐỘ CHÍNH THỨC** của backend X2-BMS (Laravel 13 + Filament 5 + PHP 8.4).
> Đối chiếu **CODE THỰC TẾ** (`D:/Code/x2/x2bms`) với **BẢN ĐỒ NGHIỆP VỤ** tại
> `handoff/x2bms/_BUSINESS_MAP_20260725/` (nguồn chuẩn phạm vi: `03_WEB_BQL`, `05_SAAS_PLATFORM_HQ`, `00_FOUNDATION`, `01_APP_CU_DAN`, `02_APP_BQL`).
>
> **Lập lần đầu:** 2026-07-27 (rà soát code vs map).
> **Cập nhật:** 2026-07-27 chiều — rà tại HEAD `738265c` (ví căn hộ · attachments/uploads · bình luận phiếu · articles PlatformContent · weather proxy).
>
> ## Quy ước trạng thái
> | Ký hiệu | Nghĩa |
> |---|---|
> | ✅ | Xong & đã verify (test/HTTP/Livewire/render 200 có ghi nhận) |
> | 🟢 | Xong nhưng CHƯA verify độc lập (code có, chưa có bằng chứng test) |
> | 🟡 | Đang làm / làm một phần |
> | ⬜ | Chưa làm |
> | ❓ | Chưa rõ scope / cần chủ dự án quyết định |
>
> ## Quy ước cập nhật
> - **Mỗi phiên / mỗi commit hoàn tất 1 module hoặc màn → cập nhật dòng tương ứng + ghi ngày.**
> - Cột "Bằng chứng" ghi Resource/Page/route/migration cụ thể (đường dẫn tương đối `app/...`).
> - Chỉ nâng lên ✅ khi có bằng chứng verify (test PASS, HTTP thật, Livewire::test, render 200 ghi trong DEV_JOURNAL).
> - Không đoán bừa: chưa chắc → để ❓ kèm ghi chú.
>
> ## Bản đồ panel ↔ tầng (đã xác nhận trong code)
> | Panel Provider | id / path | Tầng | Thư mục màn |
> |---|---|---|---|
> | `AdminPanelProvider` | `admin` `/admin` | T2b — Web BQL | `app/Filament/Pages/*` (màn nghiệp vụ BQL) |
> | `FilaPanelProvider` | `fila` `/fila` | dùng chung | `app/Filament/Resources/*` (CRUD tài nguyên) |
> | `HqPanelProvider` | `hq` `/hq` | T2a — Cổng Công ty HQ | `app/Filament/Hq/Pages/*` |
> | `SaPanelProvider` | `sa` `/sa` | T1 — SuperAdmin | `app/Filament/Sa/Pages/*` |
>
> **Quy mô code hiện tại:** 292 Model · 81 migration · ~150 Filament Resource CRUD · 58 Page BQL · 56 Page HQ · 40 Page SA · API resident (~35 route) + API platform billing/integration/support (~90 route).

---

## 1. WEB BQL (Tầng T2b — panel `/admin`)

### BQL-00 — Project Ops Foundation (nền tảng, context, quyền, audit)
| Màn | Trạng thái | Bằng chứng | Ghi chú |
|---|---|---|---|
| 00-01 Dashboard Shell | 🟢 | `Pages/OperationalDashboard.php` | Có dashboard vận hành; đối chiếu đủ 10 widget MVP chưa rõ → ❓ số widget |
| 00-02 Context Selector | ❓ | — | Chưa thấy page/control riêng cho multi-building context modes |
| 00-03 Role Workspace Switcher | 🟡 | 4 panel riêng (admin/hq/sa/fila) | Chuyển vai trò = đổi panel; chưa có switcher hợp nhất theo map |
| 00-04 Sidebar Nav theo role/module | 🟢 | PanelProviders + FeatureGate | Menu theo permission; verify feature-gate per màn chưa đủ |
| 00-05 Global Search & Quick Create | ❓ | — | Chưa xác nhận |
| 00-06 My Work & Approval Inbox | 🟢 | `Pages/MyWork.php` | |
| 00-07 Profile/Security/Sessions | 🟢 | `Pages/MyProfile.php`, `SecuritySettings.php`, `LoginSessions.php`, model `TwoFactorSetting`,`LoginSession` | |
| 00-08 Permission/Invalid Context State | 🟢 | `Pages/PermissionState.php` | |
| 00-09 Audit Log Viewer | 🟢 | `Pages/AuditLogViewer.php`, Resource `ActivityLogs`, model `AuditLog`,`ActivityLog` | |
| 00-10 Inherited Project Settings Preview | 🟢 | `Pages/ProjectSettingsPreview.php` | |

### BQL-01 — Cư dân / Căn hộ / Quan hệ cư trú
| Màn | Trạng thái | Bằng chứng | Ghi chú |
|---|---|---|---|
| 01-01 Apartment List | ✅ | `Pages/ApartmentDirectory.php` | Chuẩn listing (DEV_JOURNAL 2026-07-17, Livewire::test filter/tab/toggle) |
| 01-02 Apartment Detail | ✅ | `Pages/ApartmentProfile.php` (`/apartments/{id}/profile`) | Bản 360 giàu, 7 KPI + 7 tab; verify render 200 |
| 01-03 Apartment Create/Edit | 🟢 | Resource `Apartments` + slide-over form trong Profile | |
| 01-04 Apartment Status Timeline | 🟢 | `Pages/MoveInOutHistory.php`, model `ApartmentStatusHistory` | |
| 01-05 Resident List | ✅ | `Pages/ResidentDirectory.php` | Verify Livewire (2026-07-17) |
| 01-06 Resident Detail | 🟢 | `Pages/ResidentDetail.php` | |
| 01-07 Bind Resident Wizard | 🟢 | `Pages/ResidentCreate.php`, Resource `ResidentUnitBindings`, model `ResidentUnitBinding`,`ResidentApartmentRelation` | |
| 01-08 Binding List | 🟢 | Resource `ResidentUnitBindings`, `Pages/HouseholdRelationships.php` | |
| 01-09 Binding Timeline & History | 🟢 | `Pages/ResidentTimeline.php`, `MoveInOutHistory.php` | |
| 01-10 Vehicle Snapshot | 🟢 | model `Vehicle`, `VehiclesAndCards.php` | placeholder cross BQL-02 |
| 01-11 Import Apartments/Residents | 🟢 | `Pages/ImportHistory.php`, Resource `ImportJobs`, model `ImportBatch`,`ImportBatchRow`; trait `ImportsResidentsFromExcel` | migration mở rộng import 2026-07-20 |
| 01-12 Import Validation Review | 🟢 | `ImportBatch` (status committing) mig `2026_07_20_000002` | |
| 01-13 Export | 🟢 | Resource `ExportJobs`, model `ExportJob` | |
| 01-14 Duplicate/Merge | 🟡 | `Pages/ResidentDataQuality.php` (Rule DataQualityRules đã cắm) | merge field/fuzzy ngưỡng ❓ |
| 01-15 Empty/Locked/Permission States | 🟢 | `Pages/PermissionState.php` (dùng chung) | |

### BQL-02 — Duyệt tài khoản, Xe, Thẻ & Ra vào
| Màn | Trạng thái | Bằng chứng | Ghi chú |
|---|---|---|---|
| 02-01 Hàng đợi duyệt TK | 🟢 | `Pages/ResidentApprovalQueue.php` + chip rủi ro (Rule Engine, 2026-07-23) | |
| 02-02 Chi tiết đối chiếu | 🟢 | `Pages/AccountApprovalDetail.php` + panel rủi ro + gate `policy_block` | |
| 02-03 Duyệt đăng ký xe | 🟢 | `Pages/VehicleRequests.php`, model `VehicleRegistration` + BindingRiskRules | |
| 02-04 Chi tiết yêu cầu thẻ | 🟢 | `Pages/AccessCards.php`, model `AccessCard` | |
| 02-05 Cấp thẻ & quyền ra vào + Kích hoạt TK | 🟢 | `Pages/VehiclesAndCards.php`, `AccountActivationQueue.php` (GlobalUserAccount + MobileDevice, 2026-07-18) | |
| 02-06 Sinh trắc & QR + Chi tiết TK | 🟢 | `Pages/ResidentAccessProfile.php`, `AccountDetail.php`, model `BookingQrPass` | sinh trắc thật ❓ |
| 02-07 Hàng đợi yêu cầu bổ sung | 🟢 | `Pages/AccountChangeRequests.php` (tái dùng `data_fix_requests`) | |
| 02-08 Lịch sử xử lý duyệt | 🟢 | model `ApprovalRequest`,`ResidentApprovalRequest`,`ApprovalStep` | |
| 02-09 Thiết lập quy tắc duyệt | 🟢 | `Pages/ApprovalRuleCenter.php`, `ApprovalConflictWorkbench.php`, `app/Support/Rules/*` (test 7/7) | Rule Engine verify |
| 02-10 Dashboard duyệt & access | 🟢 | `Pages/AccessControlDashboard.php` | |

### BQL-03 — Kỳ phí, Bảng kê, Công nợ & Xuất hóa đơn ⭐
| Màn | Trạng thái | Bằng chứng | Ghi chú |
|---|---|---|---|
| 03-01 Tổng quan / Fee Cycle | 🟢 | `Pages/FeeCycleList.php`, Resource `BillingPeriods`, model `FeeCycle`(via BillingPeriod/BillingRun) | mig `2026_07_02_000008 fee_cycles_bql0302` |
| 03-02 Biểu phí / Cấu hình | 🟢 | `Pages/FeeCatalog.php`, Resource `FeeTypes`,`FeeRates`, model `FeeFormula`,`FeeFormulaVersion`,`FeeScopeAssignment` | wizard đủ bước ❓ |
| 03-03 Tạo/Chạy kỳ phí | 🟢 | model `BillingRun`,`BillingRunItem`, mig `2026_06_30_000010` lifecycle | |
| 03-04 Duyệt & Phát hành bảng kê | 🟢 | `Pages/StatementList.php`,`StatementApprovalQueue.php`, model `Statement`,`StatementApproval`,`StatementPublishLog`, mig `..._000009 approval_status` | bulk-publish ❓ |
| 03-05 Chi tiết bảng kê / Aging | 🟢 | `Pages/StatementDetail.php`,`DebtAgingList.php`, model `StatementLine` | |
| 03-06 Duyệt & phát hành | 🟢 | `StatementApprovalQueue.php` | maker-checker RBAC ❓ verify |
| 03-07 Công nợ theo căn | 🟢 | `Pages/DebtLedger.php`, model `Debt` | (map dùng `debt_ledgers`; code dùng `Debt`) ❓ đối chiếu tên bảng |
| 03-08 Điều chỉnh / Miễn giảm | 🟢 | Resource `BillingAdjustments`,`CreditNotes`, model `BillingAdjustment`,`CreditNote` | |
| 03-09 Nhắc nợ & Chiến dịch | 🟢 | model `DebtReminderCampaign`,`DebtReminderLog` | có model, chưa rõ Page |
| 03-10 Báo cáo kỳ phí | 🟡 | báo cáo tài chính ở HQ-05 | Page báo cáo cấp BQL ❓ |
| ⚠️ Công thức tính phí cụ thể | ❓ | — | GAP đã ghi trong map (phí m², xe, điện/nước bậc thang, phạt/lãi) — CHỜ QUYẾT ĐỊNH |

### BQL-04 — Thu tiền, Đối soát & Biên lai
| Màn/Nhóm | Trạng thái | Bằng chứng | Ghi chú |
|---|---|---|---|
| Thu tiền / Payment | 🟢 | Resource `Payments`,`BillingPayments`,`PaymentRequests`, model `Payment`,`PaymentAllocation`,`Receipt` | |
| Đối soát ngân hàng | 🟢 | Resource `BankTransactions`,`BillingReconciliations`, model `BankStatementImport`,`ReconciliationMatch` | |
| Biên lai / Phiếu thu chi | 🟢 | Resource `CashVouchers`, model `CashVoucher`,`CashTransaction`,`CashFund` | |
| Cổng thanh toán (VietQR/VNPay/MoMo) | 🟢 | Resource `PaymentChannels`,`PaymentGatewayConfigs`,`QrPaymentTokens` (2026-07-24) | verify route:list fila |
| **Bản đồ BQL-04 chi tiết** | ❓ | map mục BQL-04 để trống "_đang tổng hợp_" | Scope 10 màn chưa chốt trong business map |

### BQL-05 — Phản ánh, SLA & Giao việc
| Màn | Trạng thái | Bằng chứng | Ghi chú |
|---|---|---|---|
| 05-01 Hàng đợi phản ánh | 🟢 | `Pages/FeedbackQueue.php`, model `FeedbackRequest`,`FeedbackCategory`,`FeedbackStatusHistory` | |
| 05-02 Chi tiết phản ánh | 🟡 | model `FeedbackAttachment`,`FeedbackComment` | Page chi tiết riêng ❓ |
| 05-03 Tạo phản ánh hộ cư dân | 🟢 | qua Quick create / API resident feedback | |
| 05-04 Dashboard SLA & KPI | 🟡 | model `SlaEvent`,`SlaPolicy`, Resource `SlaPolicies` | dashboard riêng ❓ |
| 05-05 Quy trình & SLA | 🟢 | Resource `SlaPolicies`, model `SlaPolicy`,`SlaEvent` | ma trận giờ SLA ❓ |
| 05-06 Giao việc / Work Order | 🟢 | `Pages/WorkOrderKanban.php`, Resource `WorkOrders`, model `WorkOrder`,`WorkOrderAssignment`,`WorkOrderChecklist`,`WorkOrderMaterial`(?) | |
| 05-07 Lịch & tiến độ WO | 🟢 | `WorkOrderKanban.php` (Kanban) | Gantt/calendar ❓ |
| 05-08 Kiểm tra & nghiệm thu | 🟢 | model `WorkOrderSignature`,`WorkOrderAttachment`,`WorkOrderChecklistItem` | |
| 05-09 Đánh giá & phản hồi | 🟢 | Resource `ServiceEvaluations`, model `ServiceEvaluation` | |
| 05-10 Báo cáo phản ánh & WO | 🟡 | — | analytics riêng ❓ |

### BQL-06 — Vận hành, Bảo trì, Tài sản & Nhà thầu
| Màn | Trạng thái | Bằng chứng | Ghi chú |
|---|---|---|---|
| 06-01 Dashboard vận hành | 🟡 | (dùng OperationalDashboard) | dashboard chuyên biệt ❓ |
| 06-02 Bảng công việc bảo trì | 🟢 | `WorkOrderKanban.php` + WorkOrders resource | |
| 06-03 Lịch bảo trì định kỳ | 🟢 | Resource `MaintenancePlans`, model `MaintenancePlan` | occurrences ❓ |
| 06-04 Danh mục thiết bị/tài sản | 🟢 | Resource `Assets`,`AssetCategories`, model `Asset`,`AssetCategory` | |
| 06-05 Chi tiết thiết bị | 🟢 | Resource `Assets` (edit) + model `IotDevice`,`SensorEvent` | QR/IoT alert ❓ |
| 06-06 Tạo WO bảo trì | 🟢 | Resource `WorkOrders` | |
| 06-07 Kiểm tra & nghiệm thu | 🟢 | WorkOrder checklist/signature models | |
| 06-08 Nhà thầu & hợp đồng | 🟢 | Resource `Contractors`,`Contracts`, model `Contractor`,`Contract`,`ContractPackage`,`ContractAcceptance` | |
| 06-09 Chi tiết nhà thầu & KPI | 🟢 | model `ContractorKpi`,`ContractorSettlement` | |
| 06-10 Giao ban vận hành | 🟢 | Resource `HandoverBatches`, model `HandoverBatch`,`HandoverChecklist`,`HandoverUnit`,`HandoverPunchItem` | |
| Công tơ điện/nước (meter) | 🟢 | Resource `Meters`, model `Meter`,`MeterReading`,`EnergyReading` | ranh giới BQL-03 ❓ |

### BQL-07 — Truyền thông, Form động, Khảo sát & Cộng đồng
| Màn | Trạng thái | Bằng chứng | Ghi chú |
|---|---|---|---|
| 07-01 Dashboard truyền thông | 🟡 | `Pages/NotificationCenter.php` | dashboard KPI riêng ❓ |
| 07-02 Trung tâm thông báo & chiến dịch | 🟢 | `NotificationCenter.php`, model `Notification`,`NotificationAudience`,`NotificationChannel`,`NotificationDeliveryLog` | đa kênh SMS/Zalo thật ❓ |
| 07-03 Chi tiết & phê duyệt gửi | 🟢 | model `NotificationRead`,`NotificationDeliveryLog` | |
| 07-04 Form builder động | 🟢 | Resource `DynamicForms`,`FormFields`, model `DynamicForm`,`FormField`,`FormSection`,`FormVersion`,`FormWorkflow` | |
| 07-05 Hộp thư yêu cầu biểu mẫu | 🟢 | Resource `FormSubmissions`, model `FormSubmission`,`FormSubmissionValue` | |
| 07-06 Khảo sát & bình chọn | 🟢 | Resource `Polls`, model `Poll`,`PollOption`,`PollVote` | survey vs poll ❓ |
| 07-07 Kết quả khảo sát | 🟡 | model `PollVote` | dashboard sentiment ❓ |
| 07-08 Kiểm duyệt cộng đồng | 🟢 | Resource `CommunityPosts`, model `CommunityPost`,`Comment`(polymorphic),`CommunityGroup` | risk score/auto-hide ❓ |
| 07-09 Chi tiết bài báo cáo | 🟡 | model `CommunityPost` (flags mig 2026-07-23) | màn xử lý báo cáo riêng ❓ |
| 07-10 Phân tích hiệu quả | ⬜ | — | CTR/open-rate analytics chưa thấy |
| Sự kiện cộng đồng | 🟢 | Resource `Events`, model `Event`,`EventRegistration` | |

### BQL-08 — An ninh, Khách, Tuần tra, Bãi xe & SOS
| Màn | Trạng thái | Bằng chứng | Ghi chú |
|---|---|---|---|
| 08-01 Dashboard an ninh | 🟡 | `Pages/AccessControlDashboard.php` | KPI đủ theo map ❓ |
| 08-02 Quản lý khách | 🟢 | Resource `VisitorRegistration`(?)/model `VisitorRegistration`,`VisitorPass`,`PackageDelivery` | mig `2026_07_01_000006 visitors_and_packages` |
| 08-03 Chi tiết yêu cầu khách | 🟢 | model `VisitorRegistration`,`VisitorPass` | |
| 08-04 Check-in QR & kiosk | 🟡 | model `AccessLog`,`AccessDevice`, Resource `AccessLogs`,`AccessDevices` | kiosk phần cứng ❓ |
| 08-05 Tuần tra & checkpoint | 🟢 | Resource `PatrolRoutes`, model `PatrolRoute`,`PatrolCheckpoint`,`PatrolSession` | |
| 08-06 Chi tiết ca tuần tra | 🟢 | model `PatrolSession` | patrol_scans ❓ |
| 08-07 Bãi xe & thẻ PT | 🟢 | model `Vehicle`,`AccessCard`, `Pages/VehiclesAndCards.php` | tỷ lệ lấp đầy/LPR ❓ |
| 08-08 Giám sát sự cố & SOS | 🟢 | Resource `SecurityIncidents`,`SosAlerts`,`EmergencyAlerts`, model tương ứng + `IocAlert`,`AlertAction` | |
| 08-09 Chi tiết SOS | 🟢 | Resource `SosAlerts`, model `SosAlert` (source=app) | SLA/escalation ❓ |
| 08-10 Ca trực & bàn giao | 🟢 | Resource `Shifts`,`DutyRosters`, model `Shift`,`DutyRoster` | |
| Camera CCTV | 🟢 | Resource `Cameras`, model `Camera`,`IntercomEvent` | live embed ❓ |

### BQL-09 — Báo cáo, Phê duyệt/Ký số, Audit & X2AI
| Màn | Trạng thái | Bằng chứng | Ghi chú |
|---|---|---|---|
| 09-01 Dashboard báo cáo/duyệt/AI | 🟡 | rải rác | dashboard hợp nhất ❓ |
| 09-02 Trung tâm phê duyệt | 🟢 | Resource `ApprovalRequests`, model `ApprovalRequest`,`ApprovalStep`; `Pages/MyWork.php` | |
| 09-03 Chi tiết yêu cầu duyệt | 🟢 | Resource `ApprovalRequests` | |
| 09-04 Nhật ký & Audit | 🟢 | `Pages/AuditLogViewer.php`, model `AuditLog`,`ActivityLog` | |
| 09-05 Chi tiết bản ghi Audit | 🟢 | model `AuditLog` (before/after) | risk scoring ❓ |
| 09-06 Báo cáo tài chính & công nợ | 🟢 | (mạnh ở HQ-05) model `MetricSnapshot` | |
| 09-07 Báo cáo vận hành & SLA | 🟡 | model `SlaEvent`,`MetricSnapshot` | |
| 09-08 Export Center & lịch báo cáo | 🟢 | Resource `ExportJobs`, model `ExportJob`,`ReportExportJob`,`ReportSchedule` | |
| 09-09 X2AI Copilot | 🟢 | Resource `AiRequests`,`AiApprovals`,`AiRetrievalLogs`, model `AiChatSession`,`AiChatMessage`,`AiRequest`,`AiInsight`,`AiSuggestion`,`AiUsageLog`; trait `ProvidesAiContext` | API `ai/chat` có |
| 09-10 Phân tích AI & governance | 🟢 | Resource `AiGuardrailPolicies`, model `AiGuardrailPolicy`,`AiPolicy`,`AiPromptTemplate` | |

---

## 2. SUPERADMIN (Tầng T1 — panel `/sa`) — WEB-UX-21→30

| Batch (mã) | Trạng thái | Bằng chứng | Ghi chú |
|---|---|---|---|
| **WEB-UX-21** Package & Module Control | 🟢 | Resource `Plans`,`PlanPrices`,`PlanFeature`,`Modules`,`Features`,`TenantSubscriptions`,`TenantEntitlements`,`TenantModuleOverride`,`SubscriptionAddons`; `Sa/Pages/SubscriptionManagement.php` | Package Builder/Feature Gate Preview màn riêng ❓ |
| **WEB-UX-22** Global Account & Binding | 🟢 | `Sa/Pages/GlobalUserRegistry.php`, Resource `GlobalUserAccounts`,`ResidentBindingRequests`, model `GlobalUserAccount`,`ResidentBindingRequest` | ⚠️ xung đột mã WEB-UX-22 (bản UI vs Addendum) — CHỜ QUYẾT ĐỊNH #2 |
| **WEB-UX-23** Platform Content & Project Library | 🟢 | `Sa/Pages/PlatformContentDashboard.php`,`PlatformContentCms.php`,`PublicProjectLibrary.php`, Resource `PlatformContents`,`PublicProjects`, model `PlatformContent`,`PublicProject`,`ProjectMedia` | |
| **WEB-UX-24** Shared Contractor & Supplier | 🟢 | `Sa/Pages/ContractorLibrary.php`,`SupplierVendorLibrary.php`, Resource `SharedPartners`,`SharedPartnerCategories`,`TenantPartnerAssignments`, model `SharedPartner*` | Partner Proposal (24-10) ❓ |
| **WEB-UX-25** Document Template & Inheritance | 🟢 | `Sa/Pages/DocumentTemplateLibrary.php`,`TemplateInheritancePolicy.php`, Resource `DocumentTemplates`,`DocumentTemplateCategories`, model `DocumentTemplate`,`DocumentTemplateVersion`(?),`DocumentTemplateShare`,`DocumentTemplateClone` | |
| **WEB-UX-26** Knowledge Base & AI Governance | 🟢 | `Sa/Pages/PlatformKnowledgeBase.php`,`AiKnowledgeConfig.php`,`KnowledgeAuditLog.php`, Resource `KnowledgeDocuments`,`AiGuardrailPolicies`, model `KnowledgeDocument`,`KnowledgeScope`,`KnowledgeChunk`,`AiPromptTemplate`,`AiGuardrailPolicy` | retrieval simulator ❓ |
| **WEB-UX-27** SaaS Billing & Subscription | ✅ | `Sa/Pages/SaasRevenueDashboard.php`,`SubscriptionManagement.php`,`ContractRenewalManager.php`,`UsageMeteringDashboard.php`,`OverageQuotaAlert.php`,`InvoiceGeneration.php`,`InvoiceManagement.php`,`PassThroughWalletDashboard.php`,`BillingAuditAdjustment.php` + API 39 route `platform/billing/*` + **test Batch07 10/10 PASS** | verify test PASS (DEV_JOURNAL) |
| **WEB-UX-28** Integration Center | 🟢 | `Sa/Pages/IntegrationOverviewDashboard.php`,`ExternalConnectionManagement.php`,`ApiKeyManagement.php`,`WebhookEndpointManagement.php`,`EventLogMonitor.php`,`IntegrationHealthRetryQueue.php`,`IntegrationSecuritySettings.php` + ~40 route `platform/integrations/*` + ~15 Resource Integration* | |
| **WEB-UX-29** System Health / Job / Audit | 🟡 | `Sa/Pages/TenantBackupManager.php`,`TenantLifecycleManager.php`, model `TenantBackup`; Horizon (`HorizonServiceProvider`); mig `2026_07_21 lifecycle+backups` | THIẾU nhiều: Service Status, Job Queue Monitor, Failed Job, Cron Manager, Error Log, Tenant Data Health, Security Audit → gap lớn nhất tầng SA |
| **WEB-UX-30** Support Center / Ticket | 🟢 | `Sa/Pages/SupportDashboard.php`,`SupportTicketQueue.php`,`SupportEscalationAssignment.php`,`SupportKnowledgeBase.php`,`SupportAuditResolutionReport.php`,`DataCorrectionRequests.php`,`ControlledDataFixWizard.php`,`TenantSupportProfile.php` + API ~27 route `platform/support/*` + Resource Support* + DataFix* | Controlled Data Fix (snapshot/rollback) có model đầy đủ |
| WEB-UX-04 Design System | 🟢 | `Sa/Pages/DesignSystemSet1/2/3.php` | tham chiếu `04_WEB_UX_DESIGN_SYSTEM` |
| Tenant/Company Management gốc (đề xuất BA #5) | 🟢 | Resource `Tenants`, `Sa/Pages/TenantLifecycleManager.php` | tạo tenant + gắn HQ admin đầu ❓ |

---

## 3. HQ — Cổng Công ty (Tầng T2a — panel `/hq`) — HQ-01→05

| Batch | Trạng thái | Bằng chứng (Hq/Pages) | Ghi chú |
|---|---|---|---|
| **HQ-01** Dự án, BQL, Nhân sự, Gói | 🟢 | `ProjectDirectory`,`ProjectCreate`,`ProjectDetail`,`BqlSetup`,`EmployeeDirectory`,`ProjectAssignment`,`AssignmentHistory`,`ProjectPackage`,`ProjectModules`,`ProjectEmployeeImport` (đủ 10) + model `EmployeeProjectAssignment`,`EmployeeAssignmentHistory`,`BqlTeam` | mig `hq01_project_org` |
| **HQ-02** Billing, ví công ty, Platform | 🟢 | `SaasCostOverview`,`BillingByProject`,`CompanyWallet`,`WalletHistory`,`UsageMetering`,`PassThrough`,`PlatformInvoices`,`BillingReconciliation`,`CostForecast`,`PlanChangeRequests` (đủ 10) + model `Wallet`,`WalletTransaction`,`WalletTopupRequest`,`PlanChangeRequest` | mig `hq02_billing` |
| **HQ-03** Biểu mẫu, tài liệu, tri thức AI | 🟢 | `SharedDocuments`,`SharedForms`,`FormBuilder`,`SopChecklists`,`TemplateAssignments`,`InheritanceRules`,`KnowledgeBaseHq`,`KnowledgeHub`,`AiKnowledgeBase`,`AiKnowledgeSources`,`AiKnowledgeTest`,`AiGovernance`,`AiCenter`,`AiWorkflowAutomation` + model `DocumentLibrary`,`SopTemplate`,`TemplateAssignment`,`ConfigInheritanceRule`,`AiKnowledgeSource`,`AiWorkflow` | mig `hq03_documents` |
| **HQ-04** Phân quyền & Hỗ trợ | 🟢 | `AccessSupportOverview`,`UserManagement`,`RoleManagement`,`PermissionGroupsPage`,`PermissionMatrix`,`HqActivityLog`,`SupportTickets`,`TicketDetail`,`SlaReport`,`SupportKnowledgeBase` (đủ 10) + model `PermissionGroup`,`PermissionGroupItem` | mig `hq04_iam_support` |
| **HQ-05** Báo cáo công nợ/tài chính đa dự án | 🟢 | `FinanceOverview`,`DebtByProject`,`DebtAging`,`TopDebtors`,`CollectionRate`,`DebtByFeeType`,`Cashflow`,`DebtReminders`,`ReportExports`,`FinanceAiRisk` (đủ 10) | mig `hq05_finance` |

> HQ đạt độ phủ MÀN cao nhất (gần đủ 50/50). Cần verify dữ liệu "hai đầu" dùng chung bảng với SuperAdmin (invoice/ticket/subscription) — QUYẾT ĐỊNH #3 trong map.

---

## 4. API MOBILE (Resident + BQL)

### 4a. API Resident (app cư dân) — `routes/api.php` prefix `resident`, middleware `ability:resident`
| Nhóm endpoint | Trạng thái | Bằng chứng |
|---|---|---|
| Auth (login/register/OTP/refresh/logout) | ✅ | `AuthController`,`OtpController` (throttle) |
| Bootstrap public + me | ✅ | `BootstrapController@public/@me` (enrich city/featured/content) |
| Profile + Avatar | ✅ | `ProfileController` (PATCH profile, POST/DELETE avatar) |
| Devices (đăng ký thiết bị) | ✅ | `DeviceController`, model `MobileDevice` |
| AI Chat | 🟢 | `Ai/ChatController` (chat/sessions) |
| Statements / Billing summary + trend | ✅ | `StatementController`,`BillingSummaryController` (verify HTTP user_id=6) |
| Notifications + detail + read + comments | ✅ | `NotificationController` (+comment polymorphic 2026-07-25) |
| **Ví căn hộ** (`wallet`, `wallet/transactions`) | 🟡 | `WalletController`, `Services/Resident/ApartmentWalletService` (trừ ví theo ưu tiên, bcmath), model `ApartmentWallet`,`ApartmentWalletBucket`,`ApartmentWalletTransaction`; mig `2026_07_26_000001`; seeder `ApartmentWalletDemoSeeder`. ⚠️ **Chưa migrate/chạy lần nào** (máy dev không có PHP) — mig đã guard `hasTable`/`hasColumn` idempotent nhưng chưa verify |
| **Upload ảnh cư dân** (`POST uploads`) | 🟡 | `UploadController`, model `Attachment` (polymorphic) + trait `HasAttachments` + `AttachmentResource`; mig `2026_07_26_000002`. Disk `public` → server **bắt buộc** `php artisan storage:link`. Chưa verify |
| **Bình luận trên PHIẾU** (`{resource}/{id}/comments`) | 🟡 | `SlipCommentController` generic, whitelist `visitor-registrations`/`payments`/`amenity-bookings`, scope theo `apartment_id`; `HasComments` gắn `VisitorRegistration`,`Payment`,`AmenityBooking`. Chưa verify HTTP |
| **Articles** (quy định/cẩm nang/tin tức) | 🟡 | `ArticleController` đọc qua `PlatformContent`; seeder `ResidentArticleSeeder`. Chưa verify |
| **Weather** (Home) | 🟡 | `Services/Resident/WeatherService` — proxy Open-Meteo, mirror `AqiService`. Chưa verify |
| Loyalty (+activities/gifts) | 🟢 | `LoyaltyController`, model `LoyaltyAccount`,`LoyaltyTier`,`LoyaltyTierBenefit`,`LoyaltyTransaction` |
| Offers (vouchers) | 🟢 | `OfferController`, model `Voucher` (platform rollout) |
| Community (posts/events/polls+vote/groups) | ✅ | `CommunityController` — feed nay trả kèm `reactions`/`can` (gộp 1 lượt, tránh N+1) |
| **Community lớp GHI** (đăng/xóa/cảm xúc/bình luận/báo cáo) | ✅ | `CommunityPostController` + `CommunityModerationService` + mig `2026_07_27_100001`; verify HTTP `x2bms.test` (201/200/422/403/423/404 đúng kỳ vọng) |
| **Community kiểm duyệt BQL** (khóa/ẩn/xóa/khôi phục) | ✅ | `CommunityPostController@moderate`, nhóm `ability:resident,staff`; verify bằng tài khoản staff `nv1@x2bms.vn` (chỉ có ability `staff`, không phải cư dân) |
| Market (listings/services/categories) + Real-estate | 🟢 | `MarketController`, model `MarketplaceProduct`,`RealEstateListing` |
| Home aggregate + AQI | 🟢 | `HomeController` (Open-Meteo ENV-ready) |
| SOS | 🟢 | `SosController`, model `SosAlert` |
| Payments (history/detail) + methods + intent | 🟢 | `PaymentController`,`PaymentChannelController` (VietQR/VNPay/MoMo) |
| Apartment (căn + thành viên hộ) | 🟢 | `ApartmentController` |
| Visitors (list/create/cancel) | 🟢 | `VisitorController` |
| Amenities (list/detail/bookings/book/cancel) | 🟢 | `AmenityController` |
| Feedback (categories/list/create/detail) | 🟢 | `FeedbackController` |

> **Trạng thái tổng resident API:** ~24 route P2/P3 build + verify HTTP (commit `c08d2e6`). Do agent x2mobile build, phía x2bms điều phối domain. Nguồn: `docs/contracts/RESIDENT_API_DOMAIN.md`.
>
> ⚠️ **Nợ verify (2026-07-27):** 5 nhóm mới (ví · uploads · bình luận phiếu · articles · weather) viết trên máy **không chạy được PHP** → chưa `php -l`, chưa migrate, chưa test HTTP. Tất cả để 🟡 cho tới khi deploy x2.fino.vn và chạy:
> ```
> git pull && php artisan migrate \
>   && php artisan db:seed --class=ApartmentWalletDemoSeeder \
>   && php artisan db:seed --class=ResidentArticleSeeder \
>   && php artisan storage:link && php artisan optimize:clear
> ```

### 4b. API Platform (SuperAdmin, stateless-ish) — prefix `platform/*`, middleware `platform.admin`
| Nhóm | Trạng thái | Bằng chứng |
|---|---|---|
| `platform/billing/*` (39 route) | ✅ | 8 controller + test Batch07 10/10 PASS |
| `platform/integrations/*` (~40 route) | 🟢 | IntegrationConnection/ApiKey/Webhook/Event/RetryQueue/Security controllers |
| `platform/support/*` (~27 route) | 🟢 | SupportTicket/DataCorrection/KB controllers |

### 4c. API BQL Mobile (app BQL — theo map `02_APP_BQL`)
| | Trạng thái | Ghi chú |
|---|---|---|
| Route `/api/bql/*` cho app BQL | ⬜ | **grep `bql` trong `routes/api.php` = 0.** Chưa có API riêng cho app BQL mobile. Web BQL hiện là Filament server-rendered (`/admin`), không phải API. Map mô tả nhiều API `/api/bql/...` + realtime events → **CHƯA build.** |
| Realtime events (Pusher/Reverb) toàn hệ | ❓ | Chưa thấy broadcast events/channels theo map (FeedbackCreated, SosTriggered, ...) — cần xác nhận |

---

## 4d. Module Tài liệu (docs CMS kiểu GitBook — tự code)
| | Trạng thái | Ghi chú |
|---|---|---|
| Migrations + Models (`doc_spaces`/`doc_pages` cây + `doc_page_revisions`) | 🟢 | Đã migrate DB thật. Observer `DocPageObserver` tự sinh version khi title/body đổi (verify 1→2). |
| Soạn thảo Filament (`/sa` SuperAdmin, nav "Tài liệu") | 🟢 | `App\Filament\Sa\Resources\{DocSpaces,DocPages}`: `DocSpaceResource` + `DocPageResource` (MarkdownEditor + upload ảnh public) + `RevisionsRelationManager` (Xem + Khôi phục). |
| Phân quyền `docs.view.{6 audience}` + `docs.manage` | 🟢 | `DocsPermissionSeeder` gán 14 role. Reader lọc space theo `can(docs.view.{audience})`. |
| Reader web `/docs` (3 cột, breadcrumb, version, search) | 🟢 | `DocsController` + commonmark GFM an toàn (strip HTML). Blade tự chứa CSS, responsive. Verify render 200. |
| Command `docs:import` (dev/ + guide/) | 🟢 | Idempotent. Nạp 5 space / 11 trang. `is_public`: ops=công khai, dev/bql/hq/sa=nội bộ. |
| **Phase 2 — Site công khai `doc.x2.fino.vn`** | 🟢 | Cột `is_public` + Toggle Filament. Host routing (`config/docs.php`, root `/` landing trên subdomain). Guest xem space public, space nội bộ → login. Verify HTTP giả lập đủ case. Hạ tầng CloudPanel: `docs/guide/deploy-cloudpanel-docs-subdomain.md` (chủ dự án làm DNS+domain+SSL+ENV). |
| **Phase 3 — Polish UI reader** | 🟢 | Layout 3 cột + mục lục "Trong trang này" (anchor h2/h3 slug tiếng Việt + scrollspy sticky), hiển thị "Phiên bản N · cập nhật …" + dropdown version + banner bản cũ. Verify render trang dev nhiều heading. |
| **Phase 4 — Reader nâng cao** | 🟢 | (1) Tìm kiếm FULLTEXT (MATCH…AGAINST boolean + fallback LIKE) + snippet/highlight/anchor, tôn trọng quyền. (2) X2AI chat trong reader (tái dùng `<x-x2.ai-fab>`, CHỈ user login + `ai.use`; guest tắt — tránh chi phí). (3) Copy code (JS thuần). (4) Nút "Sửa trang" deep-link `/sa/doc-pages/{id}/edit` cho `docs.manage`. +3 chỉnh UI: bỏ H1 body trùng tiêu đề, version=dropdown luôn hiện, content full-width. |
| Phase 5 (chưa làm) | ⬜ | Ảnh theo revision, seed guide/bql\|hq\|sa, dark mode, SEO/sitemap site public. **Chờ chủ dự án:** X2AI cho guest? "version toàn tài liệu" vs revision từng trang? |

---

## 5. Việc còn lại ưu tiên (gap lớn nhất)

**0. ✅ XONG — Cộng đồng lớp GHI + kiểm duyệt (2026-07-27).**
9 route, verify HTTP thật trên `x2bms.test` bằng PHP 8.4 của Herd. 📄 Thiết kế: `docs/COMMUNITY_WRITE_MODERATION_DESIGN.md` · Hợp đồng app: `x2mobile/docs/API_REQUIREMENTS_COMMUNITY_WRITE_20260727.md`.
   - **KHÔNG duyệt trước** — đăng là `published` ngay, hậu kiểm qua `community_post_reports`.
   - Ba can thiệp BQL TÁCH BẠCH: **khóa** (`locked_at`, bài còn hiện, tương tác trả 423) · **ẩn** (`status=hidden`, tác giả vẫn thấy kèm lý do, người khác 404) · **xóa mềm** (`deleted_at`).
   - `community_post_reactions` unique(post,user) → đổi emoji là UPDATE, không cộng dồn. Lưu **mã** (`like|love|haha|wow|sad|angry`) chứ không lưu ký tự emoji.
   - Quyền do server tính (`can{}`); route `moderate` ở nhóm `ability:resident,staff` vì `ability` = OR — nhóm `ability:resident` sẽ chặn nhân sự thuần.
   - Seeder `CommunityFeedDemoSeeder`: +14 bài **nhiều tác giả** + cảm xúc thật (feed cũ 10 bài cùng một người nên rất đơn điệu).

**0b. CÒN LẠI của cụm cộng đồng — màn kiểm duyệt trên Web BQL (BQL-07-08).**
Resource `CommunityPosts` vẫn là **scaffold sinh tự động** (cột raw id, chỉ Edit/Delete mặc định, chưa theo `LISTING_PAGE_STANDARD`). Cần dựng `Pages/CommunityModeration.php`: KPI (bài mới/chờ xử lý report/đang khóa/đang ẩn), sắp xếp mặc định `report_count desc`, row action khóa/ẩn/xóa có modal nhập lý do, màn chi tiết bài kèm cây bình luận + danh sách report.

1. **⚠️ Công thức tính phí BQL-03 (CHỜ QUYẾT ĐỊNH CHỦ DỰ ÁN):** phí quản lý m²×đơn giá, gửi xe, điện/nước bậc thang, **phạt/lãi chậm nộp**. Model `FeeFormula`/`FeeFormulaVersion` đã có khung nhưng công thức nghiệp vụ chưa chốt → block tính bảng kê thật.
2. **API app BQL mobile (`/api/bql/*`) — CHƯA CÓ.** Toàn bộ Web BQL đang là Filament web. Nếu app BQL mobile (map `02_APP_BQL`) cần backend → phải build lớp API + realtime. Gap tầng lớn.
3. **WEB-UX-29 System Health (SuperAdmin):** thiếu Service Status, Job Queue Monitor, Failed Job Detail, Cron Manager, Error Log, Tenant Data Health, Security/Permission Audit. Mới có Backup + Lifecycle. Chỉ dựa Horizon.
4. **Realtime layer chưa xác nhận:** map yêu cầu nhiều broadcast event (SOS, feedback, approval, patrol) trên `private-project.{id}` — chưa thấy trong code. Cần rà `app/Events`, `config/broadcasting`.
5. **Verify độc lập còn mỏng:** phần lớn màn ở 🟢 (code có, chưa test). Chỉ BQL-01 (Livewire), Rule Engine (7/7), Batch07 billing (10/10) có bằng chứng verify. Nên bổ sung test render/Livewire cho BQL-02→09 và HQ.
6. **Đối chiếu tên bảng map vs code (❓):** map dùng `debt_ledgers`,`feedback_requests`,`work_orders`... code có `Debt`,`FeedbackRequest`,`WorkOrder` — cần xác nhận khớp cột (đặc biệt aging từ due_date, statement không có cột `currency` — bẫy đã ghi).
7. **Các "hai đầu" T1↔T2a (invoice/ticket/subscription):** xác nhận HQ và SuperAdmin đọc CÙNG bảng, tránh lệch số (QUYẾT ĐỊNH #3 map SaaS).
8. **Điểm ❓ scope trong map chưa chốt:** BQL-04 (map để trống 10 màn), ma trận SLA BQL-05, xung đột mã WEB-UX-22, mô hình 4 tầng — cần chủ dự án quyết định trước khi hoàn thiện.

---

*Cập nhật gần nhất: 2026-07-27 chiều — HEAD `738265c`. Thêm ví căn hộ · attachments/uploads · bình luận phiếu · articles · weather (tất cả 🟡 chưa verify, chờ deploy); hạ Community xuống 🟡 (chỉ đọc) và mở mục §5.0 cho slice ghi cộng đồng đang làm.*
*Lần rà trước: 2026-07-27 sáng (rà soát khởi tạo). Người rà: kỹ sư tiến độ (đối chiếu code ↔ business map).*
