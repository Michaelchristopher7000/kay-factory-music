/* ============================================================
   KAY FACTORY MUSIC — Role-based UI helpers
   Mirrors the backend policies so the UI never tempts users
   with buttons the API will reject.
   ============================================================ */

/* ---------- Artists ---------- */
export function canCreateArtist(roleSlug) {
  return ['super-admin', 'label-manager', 'ar', 'artist-manager'].includes(roleSlug);
}
export function canUpdateArtist(roleSlug) {
  return ['super-admin', 'label-manager', 'artist-manager'].includes(roleSlug);
}
export function canDeleteArtist(roleSlug) {
  return ['super-admin', 'label-manager'].includes(roleSlug);
}

/* ---------- Contracts ---------- */
export function canCreateContract(roleSlug) {
  return ['super-admin', 'label-manager', 'ar'].includes(roleSlug);
}
export function canUpdateContract(roleSlug) {
  return ['super-admin', 'label-manager'].includes(roleSlug);
}
export function canDeleteContract(roleSlug) {
  return ['super-admin', 'label-manager'].includes(roleSlug);
}

/* ---------- Tracks ---------- */
export function canCreateTrack(roleSlug) {
  return ['super-admin', 'label-manager', 'ar', 'artist-manager'].includes(roleSlug);
}
export function canUpdateTrack(roleSlug) {
  return ['super-admin', 'label-manager', 'ar', 'artist-manager'].includes(roleSlug);
}
export function canDeleteTrack(roleSlug) {
  return ['super-admin', 'label-manager'].includes(roleSlug);
}

/* ---------- Releases ---------- */
export function canCreateRelease(roleSlug) {
  return ['super-admin', 'label-manager', 'ar', 'artist-manager', 'distribution-manager'].includes(roleSlug);
}
export function canUpdateRelease(roleSlug) {
  return ['super-admin', 'label-manager', 'ar', 'artist-manager', 'distribution-manager'].includes(roleSlug);
}
export function canDeleteRelease(roleSlug) {
  return ['super-admin', 'label-manager'].includes(roleSlug);
}

/* ---------- Distributions ---------- */
export function canCreateDistribution(roleSlug) {
  return ['super-admin', 'label-manager', 'distribution-manager'].includes(roleSlug);
}
export function canUpdateDistribution(roleSlug) {
  return ['super-admin', 'label-manager', 'distribution-manager'].includes(roleSlug);
}
export function canDeleteDistribution(roleSlug) {
  return ['super-admin', 'label-manager'].includes(roleSlug);
}

/* ---------- Finance — Revenue Entries ---------- */
export function canCreateRevenueEntry(roleSlug) {
  return ['super-admin', 'label-manager', 'finance-staff'].includes(roleSlug);
}
export function canUpdateRevenueEntry(roleSlug) {
  return ['super-admin', 'label-manager', 'finance-staff'].includes(roleSlug);
}
export function canDeleteRevenueEntry(roleSlug) {
  return ['super-admin', 'label-manager'].includes(roleSlug);
}

/* ---------- Finance — Expenses ---------- */
export function canCreateExpense(roleSlug) {
  return ['super-admin', 'label-manager', 'finance-staff'].includes(roleSlug);
}
export function canUpdateExpense(roleSlug) {
  return ['super-admin', 'label-manager', 'finance-staff'].includes(roleSlug);
}
export function canDeleteExpense(roleSlug) {
  return ['super-admin', 'label-manager'].includes(roleSlug);
}

/* ---------- Finance — view gate ---------- */
export function canViewFinance(roleSlug) {
  return ['super-admin', 'label-manager', 'finance-staff'].includes(roleSlug);
}

/* ---------- Royalty Statements ---------- */
export function canCreateRoyaltyStatement(roleSlug) {
  return ['super-admin', 'label-manager', 'finance-staff'].includes(roleSlug);
}
export function canUpdateRoyaltyStatement(roleSlug) {
  return ['super-admin', 'label-manager', 'finance-staff'].includes(roleSlug);
}
export function canDeleteRoyaltyStatement(roleSlug) {
  return ['super-admin', 'label-manager'].includes(roleSlug);
}
export function canRegenerateRoyaltyStatement(roleSlug) {
  return ['super-admin', 'label-manager', 'finance-staff'].includes(roleSlug);
}
export function canEditStatementLines(roleSlug) {
  return ['super-admin', 'label-manager', 'finance-staff'].includes(roleSlug);
}

/* ---------- Royalty Payments ---------- */
export function canCreateRoyaltyPayment(roleSlug) {
  return ['super-admin', 'label-manager', 'finance-staff'].includes(roleSlug);
}
export function canUpdateRoyaltyPayment(roleSlug) {
  return ['super-admin', 'label-manager', 'finance-staff'].includes(roleSlug);
}
export function canDeleteRoyaltyPayment(roleSlug) {
  return ['super-admin', 'label-manager'].includes(roleSlug);
}

/* ---------- Royalties — view gate ---------- */
export function canViewRoyalties(roleSlug) {
  return ['super-admin', 'label-manager', 'finance-staff'].includes(roleSlug);
}

/* ---------- Reports — visibility gates ---------- */
export function canViewFinancialReports(roleSlug) {
  return ['super-admin', 'label-manager', 'finance-staff'].includes(roleSlug);
}

/* ---------- Audit Logs — visibility gate ---------- */
export function canViewAuditLogs(roleSlug) {
  return ['super-admin', 'label-manager'].includes(roleSlug);
}

/* ---------- Talent Submissions ---------- */
export function canViewTalentSubmissions(roleSlug) {
  return ['super-admin', 'label-manager'].includes(roleSlug);
}
export function canUpdateTalentSubmission(roleSlug) {
  return ['super-admin', 'label-manager'].includes(roleSlug);
}

/* ---------- Contact Messages ---------- */
export function canViewContactMessages(roleSlug) {
  return ['super-admin', 'label-manager'].includes(roleSlug);
}
export function canUpdateContactMessage(roleSlug) {
  return ['super-admin', 'label-manager'].includes(roleSlug);
}