<?php
declare(strict_types=1);

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\EventAdminController;
use App\Http\Controllers\Admin\ProgrammeAdminController;
use App\Http\Controllers\Admin\SettingsAdminController;
use App\Http\Controllers\Admin\SurveyAdminController;
use App\Http\Controllers\Api\AiController;
use App\Http\Controllers\App\AwardsAppController;
use App\Http\Controllers\App\ChallengeController;
use App\Http\Controllers\App\DashboardController;
use App\Http\Controllers\App\DealController;
use App\Http\Controllers\App\ExhibitionController;
use App\Http\Controllers\App\PaymentController;
use App\Http\Controllers\App\ProgrammeAppController;
use App\Http\Controllers\App\SurveyController;
use App\Http\Controllers\App\WorkspaceController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\PublicSite\HomeController;
use App\Http\Controllers\PublicSite\PageController;
use App\Http\Controllers\PublicSite\PublicSurveyController;
use App\Support\Router;

$router = new Router();

// Public
$router->get('/', [HomeController::class, 'index']);
$router->get('/about', [PageController::class, 'about']);
$router->get('/how-it-works', [PageController::class, 'howItWorks']);
$router->get('/challenges', [PageController::class, 'challenges']);
$router->get('/challenges/{id}', [PageController::class, 'challengeShow']);
$router->get('/innovators', [PageController::class, 'innovators']);
$router->get('/innovators/{id}', [PageController::class, 'innovatorShow']);
$router->get('/programme', [PageController::class, 'programme']);
$router->get('/schedule', [PageController::class, 'programme']);
$router->get('/programme/ics/{id}', [PageController::class, 'programmeIcs']);
$router->get('/speakers', [PageController::class, 'speakers']);
$router->get('/exhibitors', [PageController::class, 'exhibitors']);
$router->get('/exhibitors/{id}', [PageController::class, 'exhibitorShow']);
$router->get('/sponsors', [PageController::class, 'sponsors']);
$router->get('/partners', [PageController::class, 'partners']);
$router->post('/partners/inquire', [PageController::class, 'partnerInquiry']);
$router->get('/awards', [PageController::class, 'awards']);
$router->get('/deals', [PageController::class, 'deals']);
$router->get('/newsroom', [PageController::class, 'newsroom']);
$router->get('/newsroom/{slug}', [PageController::class, 'newsShow']);
$router->get('/faq', [PageController::class, 'faq']);
$router->get('/contact', [PageController::class, 'contact']);
$router->post('/contact', [PageController::class, 'contactSubmit']);
$router->get('/venue', [PageController::class, 'venue']);
$router->get('/foresight', [PageController::class, 'foresight']);
$router->post('/foresight/download', [PageController::class, 'foresightCapture']);
$router->get('/foresight/report.pdf', [PageController::class, 'foresightPdf']);
$router->get('/survey/{id}', [PublicSurveyController::class, 'show']);
$router->post('/survey/{id}', [PublicSurveyController::class, 'submit']);
$router->get('/survey/{id}/thanks', [PublicSurveyController::class, 'thanks']);
$router->get('/sitemap.xml', [PageController::class, 'sitemap']);
$router->get('/robots.txt', [PageController::class, 'robots']);
$router->get('/health', [PageController::class, 'health']);
$router->get('/style-guide', [PageController::class, 'styleGuide']);

// Auth
$router->get('/login', [LoginController::class, 'show']);
$router->get('/login.php', [LoginController::class, 'show']);
$router->post('/login', [LoginController::class, 'login']);
$router->post('/login.php', [LoginController::class, 'login']);
$router->post('/login/magic', [LoginController::class, 'requestMagic']);
$router->get('/login/magic', [LoginController::class, 'consumeMagic']);
$router->get('/login/2fa', [LoginController::class, 'showTwoFactor']);
$router->post('/login/2fa', [LoginController::class, 'challengeTwoFactor']);

$router->get('/admin/login', [LoginController::class, 'showAdmin']);
$router->get('/admin/login.php', [LoginController::class, 'showAdmin']);
$router->post('/admin/login', [LoginController::class, 'loginAdmin']);
$router->post('/admin/login.php', [LoginController::class, 'loginAdmin']);
$router->get('/admin/login/2fa', [LoginController::class, 'showAdminTwoFactor']);
$router->post('/admin/login/2fa', [LoginController::class, 'challengeAdminTwoFactor']);
$router->post('/logout', [LoginController::class, 'logout']);
$router->post('/logout-all', [LoginController::class, 'logoutAll']);
$router->get('/register', [RegisterController::class, 'show']);
$router->post('/register', [RegisterController::class, 'submit']);
$router->post('/register/account', [RegisterController::class, 'createAccount']);
$router->post('/register/draft', [RegisterController::class, 'saveDraft']);
$router->get('/register/chat', [RegisterController::class, 'chatPage']);
$router->get('/register/done', [RegisterController::class, 'done']);
$router->get('/verify-email', [RegisterController::class, 'verifyEmail']);
$router->get('/verify-email/otp', [RegisterController::class, 'showOtp']);
$router->post('/verify-email/otp', [RegisterController::class, 'submitOtp']);
$router->post('/verify-email/resend', [RegisterController::class, 'resend']);
$router->get('/password/forgot', [\App\Http\Controllers\Auth\PasswordResetController::class, 'showForgot']);
$router->post('/password/forgot', [\App\Http\Controllers\Auth\PasswordResetController::class, 'sendForgot']);
$router->get('/password/reset', [\App\Http\Controllers\Auth\PasswordResetController::class, 'showReset']);
$router->post('/password/reset', [\App\Http\Controllers\Auth\PasswordResetController::class, 'reset']);

// API AI
$router->post('/api/v1/ai/chat/start', [AiController::class, 'chatStart']);
$router->post('/api/v1/ai/chat/message', [AiController::class, 'chatMessage']);
$router->post('/api/v1/ai/ask', [AiController::class, 'ask']);
$router->get('/api/v1/ai/health', [AiController::class, 'health']);
$router->post('/api/v1/ai/match/{challengeId}/recompute', [AiController::class, 'recomputeMatch']);

// App
$router->get('/app', [DashboardController::class, 'index']);
$router->get('/app/style-guide', [DashboardController::class, 'styleGuide']);
$router->get('/app/profile', [DashboardController::class, 'profile']);
$router->post('/app/profile', [DashboardController::class, 'profileSave']);
$router->post('/app/sessions/revoke', [DashboardController::class, 'revokeSession']);
$router->get('/app/security/2fa', [\App\Http\Controllers\App\SecurityController::class, 'showTwoFactor']);
$router->post('/app/security/2fa/enable', [\App\Http\Controllers\App\SecurityController::class, 'enable']);
$router->post('/app/security/2fa/confirm', [\App\Http\Controllers\App\SecurityController::class, 'confirm']);
$router->post('/app/security/2fa/disable', [\App\Http\Controllers\App\SecurityController::class, 'disable']);
$router->get('/app/privacy/export', [\App\Http\Controllers\App\PrivacyController::class, 'export']);
$router->post('/app/privacy/delete', [\App\Http\Controllers\App\PrivacyController::class, 'deleteRequest']);
$router->get('/app/notifications', [DashboardController::class, 'notifications']);
$router->post('/app/notifications/read', [DashboardController::class, 'markNotificationRead']);
$router->get('/app/notifications/poll', [DashboardController::class, 'notificationsPoll']);
$router->get('/app/notifications/preferences', [DashboardController::class, 'notificationPreferences']);
$router->post('/app/notifications/preferences', [DashboardController::class, 'notificationPreferencesSave']);

$router->get('/app/challenges', [ChallengeController::class, 'index']);
$router->get('/app/challenges/create', [ChallengeController::class, 'create']);
$router->post('/app/challenges', [ChallengeController::class, 'store']);
$router->get('/app/challenges/{id}', [ChallengeController::class, 'show']);
$router->post('/app/challenges/{id}/claim', [ChallengeController::class, 'claim']);
$router->post('/app/challenges/{id}/interest', [ChallengeController::class, 'expressInterest']);
$router->post('/app/challenges/{id}/matches', [ChallengeController::class, 'recomputeMatches']);

$router->get('/app/solutions', [\App\Http\Controllers\App\SolutionController::class, 'index']);
$router->get('/app/solutions/create', [\App\Http\Controllers\App\SolutionController::class, 'create']);
$router->post('/app/solutions', [\App\Http\Controllers\App\SolutionController::class, 'store']);
$router->get('/app/solutions/{id}/edit', [\App\Http\Controllers\App\SolutionController::class, 'edit']);
$router->post('/app/solutions/{id}', [\App\Http\Controllers\App\SolutionController::class, 'update']);
$router->get('/app/matches', [\App\Http\Controllers\App\SolutionController::class, 'matches']);
$router->post('/app/connections/request', [DealController::class, 'requestConnection']);

$router->get('/app/workspaces', [WorkspaceController::class, 'index']);
$router->get('/app/workspaces/{id}', [WorkspaceController::class, 'show']);
$router->post('/app/workspaces/{id}/feedback', [WorkspaceController::class, 'addFeedback']);
$router->post('/app/workspaces/{id}/milestone', [WorkspaceController::class, 'updateMilestone']);
$router->post('/app/workspaces/{id}/ready', [WorkspaceController::class, 'markReady']);
$router->post('/app/workspaces/{id}/coaching', [WorkspaceController::class, 'bookCoaching']);

$router->get('/app/deals', [DealController::class, 'index']);
$router->get('/app/deals/{id}', [DealController::class, 'show']);
$router->post('/app/deals/{id}/messages', [DealController::class, 'postMessage']);
$router->get('/app/deals/{id}/messages', [DealController::class, 'pollMessages']);
$router->post('/app/deals/{id}/stage', [DealController::class, 'moveStage']);
$router->post('/app/deals/{id}/outcome', [DealController::class, 'tagOutcome']);
$router->post('/app/deals/{id}/files', [DealController::class, 'uploadFile']);
$router->get('/app/deals/{id}/files/{fileId}/download', [DealController::class, 'downloadFile']);
$router->post('/app/deals/{id}/meeting', [DealController::class, 'bookMeeting']);
$router->post('/app/deals/{id}/nda', [DealController::class, 'acceptNda']);
$router->post('/app/deals/{id}/checklist', [DealController::class, 'toggleChecklist']);
$router->post('/app/connections/{requestId}/accept', [DealController::class, 'acceptConnection']);
$router->get('/app/investor', [DealController::class, 'investorFlow']);
$router->post('/app/investor/watch', [DealController::class, 'watchlistToggle']);
$router->post('/app/investor/stage', [DealController::class, 'investorStage']);
$router->post('/app/investor/notes', [DealController::class, 'saveNote']);
$router->post('/app/investor/intro', [DealController::class, 'requestIntro']);
$router->get('/app/investor/{solutionId}/onepager.pdf', [DealController::class, 'investorPdf']);

$router->get('/app/programme', [ProgrammeAppController::class, 'agenda']);
$router->post('/app/programme/toggle', [ProgrammeAppController::class, 'toggleAgenda']);
$router->get('/app/meetings', [ProgrammeAppController::class, 'meetings']);
$router->post('/app/meetings', [ProgrammeAppController::class, 'requestMeeting']);
$router->post('/app/meetings/respond', [ProgrammeAppController::class, 'respondMeeting']);
$router->post('/app/meetings/reschedule', [ProgrammeAppController::class, 'rescheduleMeeting']);
$router->get('/app/meetings/{id}/ics', [ProgrammeAppController::class, 'ics']);

$router->get('/app/surveys', [SurveyController::class, 'index']);
$router->get('/app/surveys/{id}', [SurveyController::class, 'show']);
$router->post('/app/surveys/{id}', [SurveyController::class, 'submit']);

$router->get('/app/exhibition', [ExhibitionController::class, 'booth']);
$router->post('/app/exhibition', [ExhibitionController::class, 'saveBooth']);
$router->post('/app/exhibition/leads', [ExhibitionController::class, 'captureLead']);
$router->post('/app/exhibition/leads/update', [ExhibitionController::class, 'updateLead']);
$router->get('/app/exhibition/leads.csv', [ExhibitionController::class, 'exportLeads']);
$router->get('/app/exhibit/apply', [ExhibitionController::class, 'applyForm']);
$router->post('/app/exhibit/apply', [ExhibitionController::class, 'applySubmit']);

$router->get('/app/awards/nominate', [AwardsAppController::class, 'nominate']);
$router->post('/app/awards/nominate', [AwardsAppController::class, 'storeNomination']);
$router->get('/app/sponsorship', [AwardsAppController::class, 'applySponsorship']);
$router->post('/app/sponsorship', [AwardsAppController::class, 'storeSponsorship']);
$router->get('/app/sponsor-portal', [AwardsAppController::class, 'sponsorPortal']);
$router->post('/app/sponsor-portal/assets', [AwardsAppController::class, 'sponsorAssetsSave']);

$router->get('/app/payments/return', [PaymentController::class, 'returnFromPaynow']);
$router->post('/app/payments/start', [PaymentController::class, 'startRegistrationPayment']);
$router->get('/app/payments/{reference}', [PaymentController::class, 'show']);
$router->post('/app/payments/{reference}/simulate', [PaymentController::class, 'simulatePay']);
$router->post('/api/v1/payments/paynow/result', [PaymentController::class, 'paynowResult']);

// Admin
$router->get('/dashboard', [AdminController::class, 'dashboard']);
$router->get('/admin', static function (): void {
    \App\Support\Response::redirect('/dashboard');
});
$router->get('/admin/', static function (): void {
    \App\Support\Response::redirect('/dashboard');
});
$router->get('/admin/events', [EventAdminController::class, 'index']);
$router->post('/admin/events', [EventAdminController::class, 'store']);
$router->post('/admin/events/switch', [EventAdminController::class, 'switchEvent']);
$router->post('/admin/events/{id}', [EventAdminController::class, 'update']);
$router->get('/admin/screening', [AdminController::class, 'screening']);
$router->post('/admin/screening', [AdminController::class, 'screenAction']);
$router->get('/admin/matching', [AdminController::class, 'matching']);
$router->post('/admin/matching', [AdminController::class, 'matchingRun']);
$router->post('/admin/mentors', [AdminController::class, 'assignMentor']);
$router->get('/admin/impact', [AdminController::class, 'impact']);
$router->get('/admin/impact/export.csv', [AdminController::class, 'impactExport']);
$router->get('/admin/impact/export.pdf', [AdminController::class, 'impactPdf']);
$router->get('/admin/surveys', [AdminController::class, 'surveys']);
$router->post('/admin/surveys', [AdminController::class, 'surveyStore']);
$router->get('/admin/surveys/{id}/edit', [SurveyAdminController::class, 'edit']);
$router->post('/admin/surveys/{id}/questions', [SurveyAdminController::class, 'addQuestion']);
$router->post('/admin/surveys/{id}/logic', [SurveyAdminController::class, 'addLogic']);
$router->post('/admin/surveys/{id}/publish', [SurveyAdminController::class, 'publish']);
$router->post('/admin/surveys/{id}/deliver', [SurveyAdminController::class, 'deliver']);
$router->get('/admin/surveys/{id}/analytics', [SurveyAdminController::class, 'richResults']);
$router->get('/admin/surveys/{id}/results', [SurveyAdminController::class, 'richResults']);
$router->get('/admin/surveys/{id}/export.csv', [AdminController::class, 'surveyExport']);
$router->get('/admin/surveys/{id}/export.pdf', [AdminController::class, 'surveyPdf']);
$router->get('/admin/sponsorship', [AdminController::class, 'sponsorshipBoard']);
$router->post('/admin/sponsorship', [AdminController::class, 'sponsorshipAction']);
$router->get('/admin/awards', [AdminController::class, 'awardsJudge']);
$router->post('/admin/awards', [AdminController::class, 'awardsScore']);
$router->get('/admin/users', [AdminController::class, 'users']);
$router->post('/admin/users/create', [AdminController::class, 'createUser']);
$router->post('/admin/users/role', [AdminController::class, 'assignUserRole']);
$router->get('/admin/organizations', [AdminController::class, 'organizations']);
$router->get('/admin/reports', [AdminController::class, 'reports']);
$router->get('/admin/settings', [SettingsAdminController::class, 'index']);
$router->post('/admin/settings', [SettingsAdminController::class, 'save']);
$router->post('/admin/settings/test', [SettingsAdminController::class, 'test']);
$router->post('/admin/settings/flags', [SettingsAdminController::class, 'toggleFlag']);
$router->get('/admin/programme', [ProgrammeAdminController::class, 'index']);
$router->post('/admin/programme', [ProgrammeAdminController::class, 'store']);
$router->post('/admin/programme/{id}', [ProgrammeAdminController::class, 'update']);
$router->post('/admin/programme/{id}/delete', [ProgrammeAdminController::class, 'delete']);
$router->get('/admin/audit', [AdminController::class, 'audit']);
$router->get('/admin/cms', [AdminController::class, 'cms']);
$router->post('/admin/cms/faq', [AdminController::class, 'cmsFaqSave']);
$router->post('/admin/cms/news', [AdminController::class, 'cmsNewsSave']);
$router->post('/admin/cms/page', [AdminController::class, 'cmsPageSave']);
$router->post('/admin/cms/testimonial', [AdminController::class, 'cmsTestimonialSave']);
$router->post('/admin/cms/foresight', [AdminController::class, 'cmsForesightSave']);
$router->get('/admin/comms', [AdminController::class, 'comms']);
$router->post('/admin/comms', [AdminController::class, 'commsSend']);
$router->get('/admin/registrations', [AdminController::class, 'registrations']);
$router->post('/admin/registrations/action', [AdminController::class, 'registrationAction']);
$router->get('/admin/registrations/export.csv', [AdminController::class, 'registrationsExport']);
$router->get('/admin/system', static function (): void {
    \App\Support\Response::redirect('/admin/settings?tab=flags');
});
$router->post('/admin/system/flags', [SettingsAdminController::class, 'toggleFlag']);
$router->get('/admin/deals', [AdminController::class, 'dealsOversight']);
$router->post('/admin/deals/stage', [AdminController::class, 'dealForceStage']);
$router->get('/admin/deals/export.csv', [AdminController::class, 'dealsExport']);
$router->get('/admin/inquiries', [AdminController::class, 'inquiries']);
$router->post('/admin/inquiries', [AdminController::class, 'inquiryAction']);
$router->get('/admin/booths', [AdminController::class, 'booths']);
$router->post('/admin/booths', [AdminController::class, 'boothAssign']);

return $router;
