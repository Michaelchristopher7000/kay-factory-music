import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap-icons/font/bootstrap-icons.css';
import 'bootstrap/dist/js/bootstrap.bundle.min.js';
import '../css/app.css';


import React from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';

import DashboardLayout from './staff/DashboardLayout';
import Dashboard from './staff/Dashboard';
import LoginPage from './staff/LoginPage';
import RequireAuth from './staff/RequireAuth';
import SettingsPage from './staff/pages/Settings/SettingsPage';
import ForgotPasswordPage from './staff/pages/ForgotPasswordPage';
import ResetPasswordPage from './staff/pages/ResetPasswordPage';

/* Artists */
import ArtistsListPage from './staff/pages/Artists/ArtistsListPage';
import ArtistFormPage from './staff/pages/Artists/ArtistFormPage';
import ArtistViewPage from './staff/pages/Artists/ArtistViewPage';

/* Contracts */
import ContractsListPage from './staff/pages/Contracts/ContractsListPage';
import ContractFormPage from './staff/pages/Contracts/ContractFormPage';
import ContractViewPage from './staff/pages/Contracts/ContractViewPage';

/* Tracks */
import TracksListPage from './staff/pages/Tracks/TracksListPage';
import TrackFormPage from './staff/pages/Tracks/TrackFormPage';
import TrackViewPage from './staff/pages/Tracks/TrackViewPage';

/* Releases */
import ReleasesListPage from './staff/pages/Releases/ReleasesListPage';
import ReleaseFormPage from './staff/pages/Releases/ReleaseFormPage';
import ReleaseViewPage from './staff/pages/Releases/ReleaseViewPage';

/* Artist Videos */
import ArtistVideosListPage from './staff/pages/ArtistVideos/ArtistVideosListPage';
import ArtistVideoFormPage from './staff/pages/ArtistVideos/ArtistVideoFormPage';

/* Artist Gallery */
import ArtistGalleryListPage from './staff/pages/ArtistGallery/ArtistGalleryListPage';
import ArtistGalleryFormPage from './staff/pages/ArtistGallery/ArtistGalleryFormPage';

/* Artist Events */
import ArtistEventsListPage from './staff/pages/ArtistEvents/ArtistEventsListPage';
import ArtistEventFormPage from './staff/pages/ArtistEvents/ArtistEventFormPage';
import ArtistEventViewPage from './staff/pages/ArtistEvents/ArtistEventViewPage';

/* Talent Submissions */
import TalentSubmissionsListPage from './staff/pages/TalentSubmissions/TalentSubmissionsListPage';
import TalentSubmissionViewPage from './staff/pages/TalentSubmissions/TalentSubmissionViewPage';

/* Contact Messages */
import ContactMessagesListPage from './staff/pages/ContactMessages/ContactMessagesListPage';
import ContactMessageViewPage from './staff/pages/ContactMessages/ContactMessageViewPage';

/* Distributions */
import DistributionsListPage from './staff/pages/Distributions/DistributionsListPage';
import DistributionFormPage from './staff/pages/Distributions/DistributionFormPage';
import DistributionViewPage from './staff/pages/Distributions/DistributionViewPage';

/* Finance */
import FinanceHubPage from './staff/pages/Finance/FinanceHubPage';
import RevenueEntriesListPage from './staff/pages/RevenueEntries/RevenueEntriesListPage';
import RevenueEntryFormPage from './staff/pages/RevenueEntries/RevenueEntryFormPage';
import RevenueEntryViewPage from './staff/pages/RevenueEntries/RevenueEntryViewPage';
import ExpensesListPage from './staff/pages/Expenses/ExpensesListPage';
import ExpenseFormPage from './staff/pages/Expenses/ExpenseFormPage';
import ExpenseViewPage from './staff/pages/Expenses/ExpenseViewPage';

/* Royalties */
import RoyaltiesHubPage from './staff/pages/Royalties/RoyaltiesHubPage';
import RoyaltyStatementsListPage from './staff/pages/RoyaltyStatements/RoyaltyStatementsListPage';
import RoyaltyStatementFormPage from './staff/pages/RoyaltyStatements/RoyaltyStatementFormPage';
import RoyaltyStatementViewPage from './staff/pages/RoyaltyStatements/RoyaltyStatementViewPage';
import RoyaltyPaymentsListPage from './staff/pages/RoyaltyPayments/RoyaltyPaymentsListPage';
import RoyaltyPaymentFormPage from './staff/pages/RoyaltyPayments/RoyaltyPaymentFormPage';
import RoyaltyPaymentViewPage from './staff/pages/RoyaltyPayments/RoyaltyPaymentViewPage';

/* Reports */
import ReportsHubPage from './staff/pages/Reports/ReportsHubPage';
import ArtistSummaryPage from './staff/pages/Reports/ArtistSummaryPage';
import ReleasePerformancePage from './staff/pages/Reports/ReleasePerformancePage';
import DistributionStatusPage from './staff/pages/Reports/DistributionStatusPage';
import RevenueBySourcePage from './staff/pages/Reports/RevenueBySourcePage';
import ExpenseByCategoryPage from './staff/pages/Reports/ExpenseByCategoryPage';
import RoyaltyBalancesPage from './staff/pages/Reports/RoyaltyBalancesPage';

/* Audit Logs */
import AuditLogsListPage from './staff/pages/AuditLogs/AuditLogsListPage';

/* Notifications */
import NotificationsPage from './staff/pages/Notifications/NotificationsPage';

/* Devices */
import DevicesPage from './staff/pages/Devices/DevicesPage';

/* Messages */
import MessagesPage from './staff/pages/Messages/MessagesPage';

const root = document.getElementById('app');

createRoot(root).render(
  <BrowserRouter basename="/staff">
      <Routes>
      {/* Public staff auth (no RequireAuth) */}
      <Route path="login" element={<LoginPage />} />
      <Route path="forgot-password" element={<ForgotPasswordPage />} />
      <Route path="reset-password/:token" element={<ResetPasswordPage />} />

      {/* Protected staff area */}
      <Route element={<RequireAuth><DashboardLayout /></RequireAuth>}>
        <Route index element={<Dashboard />} />

        {/* Settings */}
        <Route path="settings" element={<SettingsPage />} />

        {/* Artists */}
        <Route path="artists" element={<ArtistsListPage />} />
        <Route path="artists/new" element={<ArtistFormPage />} />
        <Route path="artists/:id" element={<ArtistViewPage />} />
        <Route path="artists/:id/edit" element={<ArtistFormPage />} />

        {/* Contracts */}
        <Route path="contracts" element={<ContractsListPage />} />
        <Route path="contracts/new" element={<ContractFormPage />} />
        <Route path="contracts/:id" element={<ContractViewPage />} />
        <Route path="contracts/:id/edit" element={<ContractFormPage />} />

        {/* Tracks */}
        <Route path="tracks" element={<TracksListPage />} />
        <Route path="tracks/new" element={<TrackFormPage />} />
        <Route path="tracks/:id" element={<TrackViewPage />} />
        <Route path="tracks/:id/edit" element={<TrackFormPage />} />

        {/* Releases */}
        <Route path="releases" element={<ReleasesListPage />} />
        <Route path="releases/new" element={<ReleaseFormPage />} />
        <Route path="releases/:id" element={<ReleaseViewPage />} />
        <Route path="releases/:id/edit" element={<ReleaseFormPage />} />

        {/* Artist Videos */}
        <Route path="artist-videos" element={<ArtistVideosListPage />} />
        <Route path="artist-videos/new" element={<ArtistVideoFormPage />} />
        <Route path="artist-videos/:id/edit" element={<ArtistVideoFormPage />} />

        {/* Artist Gallery */}
        <Route path="artist-gallery" element={<ArtistGalleryListPage />} />
        <Route path="artist-gallery/new" element={<ArtistGalleryFormPage />} />
        <Route path="artist-gallery/:id/edit" element={<ArtistGalleryFormPage />} />

        {/* Artist Events */}
        <Route path="artist-events" element={<ArtistEventsListPage />} />
        <Route path="artist-events/new" element={<ArtistEventFormPage />} />
        <Route path="artist-events/:id" element={<ArtistEventViewPage />} />
        <Route path="artist-events/:id/edit" element={<ArtistEventFormPage />} />

        {/* Talent Submissions */}
        <Route path="talent-submissions" element={<TalentSubmissionsListPage />} />
        <Route path="talent-submissions/:id" element={<TalentSubmissionViewPage />} />

        {/* Contact Messages */}
        <Route path="contact-messages" element={<ContactMessagesListPage />} />
        <Route path="contact-messages/:id" element={<ContactMessageViewPage />} />

        {/* Messages */}
        <Route path="messages" element={<MessagesPage />} />
        <Route path="messages/:conversationId" element={<MessagesPage />} />

        {/* Distributions */}
        <Route path="distributions" element={<DistributionsListPage />} />
        <Route path="distributions/new" element={<DistributionFormPage />} />
        <Route path="distributions/:id" element={<DistributionViewPage />} />
        <Route path="distributions/:id/edit" element={<DistributionFormPage />} />

        {/* Finance */}
        <Route path="finance" element={<FinanceHubPage />} />
        <Route path="revenue-entries" element={<RevenueEntriesListPage />} />
        <Route path="revenue-entries/new" element={<RevenueEntryFormPage />} />
        <Route path="revenue-entries/:id" element={<RevenueEntryViewPage />} />
        <Route path="revenue-entries/:id/edit" element={<RevenueEntryFormPage />} />
        <Route path="expenses" element={<ExpensesListPage />} />
        <Route path="expenses/new" element={<ExpenseFormPage />} />
        <Route path="expenses/:id" element={<ExpenseViewPage />} />
        <Route path="expenses/:id/edit" element={<ExpenseFormPage />} />

        {/* Royalties */}
        <Route path="royalties" element={<RoyaltiesHubPage />} />
        <Route path="royalty-statements" element={<RoyaltyStatementsListPage />} />
        <Route path="royalty-statements/new" element={<RoyaltyStatementFormPage />} />
        <Route path="royalty-statements/:id" element={<RoyaltyStatementViewPage />} />
        <Route path="royalty-statements/:id/edit" element={<RoyaltyStatementFormPage />} />
        <Route path="royalty-payments" element={<RoyaltyPaymentsListPage />} />
        <Route path="royalty-payments/new" element={<RoyaltyPaymentFormPage />} />
        <Route path="royalty-payments/:id" element={<RoyaltyPaymentViewPage />} />
        <Route path="royalty-payments/:id/edit" element={<RoyaltyPaymentFormPage />} />

        {/* Reports */}
        <Route path="reports" element={<ReportsHubPage />} />
        <Route path="reports/artists-summary" element={<ArtistSummaryPage />} />
        <Route path="reports/releases-performance" element={<ReleasePerformancePage />} />
        <Route path="reports/distributions-status" element={<DistributionStatusPage />} />
        <Route path="reports/revenue-by-source" element={<RevenueBySourcePage />} />
        <Route path="reports/expenses-by-category" element={<ExpenseByCategoryPage />} />
        <Route path="reports/royalties-balances" element={<RoyaltyBalancesPage />} />

        {/* Audit Logs */}
        <Route path="audit-logs" element={<AuditLogsListPage />} />

        {/* Notifications */}
        <Route path="notifications" element={<NotificationsPage />} />

        {/* Devices */}
        <Route path="devices" element={<DevicesPage />} />

        <Route path="*" element={<Navigate to="/" replace />} />
      </Route>
    </Routes>
  </BrowserRouter>
);