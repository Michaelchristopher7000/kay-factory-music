import { useEffect, useState, useCallback } from "react";
import { Link } from "react-router-dom";
import api from "./api";
import { useAuth } from "./auth";

/* ---------- Role helpers ---------- */

const canSeeFinance = (slug) =>
    ["super-admin", "label-manager", "finance-staff"].includes(slug);
const canSeeAudit = (slug) => ["super-admin", "label-manager"].includes(slug);

const STATUS_CLASS = {
    signed: "kfm-badge--green",
    released: "kfm-badge--green",
    active: "kfm-badge--green",
    live: "kfm-badge--green",
    paid: "kfm-badge--green",
    in_talks: "kfm-badge--gold",
    pending_signature: "kfm-badge--gold",
    pending: "kfm-badge--gold",
    submitted: "kfm-badge--blue",
    scheduled: "kfm-badge--blue",
    issued: "kfm-badge--blue",
    draft: "kfm-badge--muted",
    inactive: "kfm-badge--muted",
    former: "kfm-badge--muted",
    cancelled: "kfm-badge--muted",
    archived: "kfm-badge--muted",
    failed: "kfm-badge--pink",
    rejected: "kfm-badge--pink",
    terminated: "kfm-badge--pink",
};

const statusLabel = (s) => (s || "").replace(/_/g, " ");

const formatMoney = (v, currency = "NGN") =>
    `${currency} ${Number(v || 0).toLocaleString(undefined, { maximumFractionDigits: 0 })}`;

const formatRelative = (iso) => {
    if (!iso) return "";
    const diff = Date.now() - new Date(iso).getTime();
    const mins = Math.floor(diff / 60000);
    if (mins < 60) return `${mins}m ago`;
    const hrs = Math.floor(mins / 60);
    if (hrs < 24) return `${hrs}h ago`;
    const days = Math.floor(hrs / 24);
    if (days < 30) return `${days}d ago`;
    return new Date(iso).toLocaleDateString();
};

/* ---------- Sub-components ---------- */

function StatCard({ icon, label, value, delta, tone = "gold" }) {
    return (
        <div className={`kfm-stat kfm-stat--${tone}`}>
            <div className="kfm-stat__icon">
                <i className={`bi ${icon}`}></i>
            </div>
            <div className="kfm-stat__body">
                <div className="kfm-stat__label">{label}</div>
                <div className="kfm-stat__value">{value}</div>
                {delta && <div className="kfm-stat__delta">{delta}</div>}
            </div>
        </div>
    );
}

function QuickAction({ icon, label, to }) {
    return (
        <Link to={to} className="kfm-quick">
            <i className={`bi ${icon}`}></i>
            <span>{label}</span>
        </Link>
    );
}

function ActivityItem({ log }) {
    const icon = (() => {
        switch (log.action) {
            case "created":
                return "bi-plus-circle";
            case "updated":
                return "bi-pencil-square";
            case "deleted":
                return "bi-trash";
            case "restored":
                return "bi-arrow-counterclockwise";
            default:
                return "bi-activity";
        }
    })();

    const color = (() => {
        switch (log.action) {
            case "created":
                return "#4ADE80";
            case "updated":
                return "#60A5FA";
            case "deleted":
                return "#F87171";
            case "restored":
                return "#E4B84C";
            default:
                return "#8A8A94";
        }
    })();

    return (
        <div className="kfm-activity">
            <div
                className="kfm-activity__icon"
                style={{ color, background: `${color}1A` }}
            >
                <i className={`bi ${icon}`}></i>
            </div>
            <div className="kfm-activity__body">
                <div className="kfm-activity__title">
                    {log.model_label || `${log.model_type} #${log.model_id}`}
                </div>
                <div className="kfm-activity__detail">
                    {statusLabel(log.action)} by {log.user_name || "system"}
                </div>
            </div>
            <div className="kfm-activity__time">
                {formatRelative(log.created_at)}
            </div>
        </div>
    );
}

/* ---------- Main ---------- */

export default function Dashboard() {
    const { user } = useAuth();
    const roleSlug = user?.role?.slug || "";
    const firstName = (user?.name || "there").split(" ")[0];

    const showFinance = canSeeFinance(roleSlug);
    const showAudit = canSeeAudit(roleSlug);

    const [data, setData] = useState({
        artists: [],
        artistsTotal: 0,
        releases: [],
        releasesTotal: 0,
        activeContracts: 0,
        recentArtists: [],
        latestReleases: [],
        revenueTotal: 0,
        revenueMonths: [],
        activity: [],
    });

    const [loading, setLoading] = useState(true);
    const [error, setError] = useState("");

    const fetchAll = useCallback(async () => {
        setLoading(true);
        setError("");

        const safe = async (fn, fallback) => {
            try {
                return await fn();
            } catch (err) {
                if (err.response?.status === 401) throw err; // let interceptor handle
                return fallback;
            }
        };

        try {
            // Core stats + lists — always fetched
            const [artistsRes, releasesRes, contractsRes] = await Promise.all([
                safe(() => api.get("/artists", { params: { per_page: 5 } }), {
                    data: { data: [], meta: {} },
                }),
                safe(() => api.get("/releases", { params: { per_page: 5 } }), {
                    data: { data: [], meta: {} },
                }),
                safe(
                    () =>
                        api.get("/contracts", {
                            params: { status: "active", per_page: 1 },
                        }),
                    { data: { meta: {} } },
                ),
            ]);

            const next = {
                recentArtists: artistsRes.data?.data || [],
                artistsTotal: artistsRes.data?.meta?.total || 0,
                latestReleases: releasesRes.data?.data || [],
                releasesTotal: releasesRes.data?.meta?.total || 0,
                activeContracts: contractsRes.data?.meta?.total || 0,
                revenueTotal: 0,
                revenueMonths: [],
                activity: [],
            };

            // Financial stats — only for privileged roles
            if (showFinance) {
                const revenueRes = await safe(
                    () =>
                        api.get("/revenue-entries", {
                            params: { per_page: 100 },
                        }),
                    { data: { data: [], meta: {} } },
                );

                const entries = revenueRes.data?.data || [];

                next.revenueTotal = entries.reduce(
                    (sum, e) => sum + Number(e.amount || 0),
                    0,
                );

                // Aggregate last 6 months by period_end
                const months = {};
                const now = new Date();

                for (let i = 5; i >= 0; i--) {
                    const d = new Date(
                        now.getFullYear(),
                        now.getMonth() - i,
                        1,
                    );
                    const key = d.toLocaleString("en-US", { month: "short" });
                    months[key] = 0;
                }

                entries.forEach((e) => {
                    if (!e.period_end) return;
                    const d = new Date(e.period_end);
                    const key = d.toLocaleString("en-US", { month: "short" });
                    if (key in months) months[key] += Number(e.amount || 0);
                });

                next.revenueMonths = Object.entries(months).map(
                    ([month, value]) => ({ month, value }),
                );
            }

            // Audit feed — only for SA + LM
            if (showAudit) {
                const auditRes = await safe(
                    () => api.get("/audit-logs", { params: { per_page: 5 } }),
                    { data: { data: [] } },
                );
                next.activity = auditRes.data?.data || [];
            }

            setData(next);
        } catch (err) {
            if (err.response?.status === 401) return;
            setError("Could not load dashboard data.");
        } finally {
            setLoading(false);
        }
    }, [showFinance, showAudit]);

    useEffect(() => {
        fetchAll();
    }, [fetchAll]);

    const maxRev = Math.max(1, ...data.revenueMonths.map((d) => d.value));

    return (
        <div className="kfm-dash">
            {/* Welcome Hero */}
            <section className="kfm-welcome">
                <video
                    className="kfm-welcome__video"
                    autoPlay
                    muted
                    loop
                    playsInline
                    preload="metadata"
                >
                    <source src="/videos/kfm-dashboard.mp4" type="video/mp4" />
                </video>

                <div className="kfm-welcome__overlay"></div>

                <div className="kfm-welcome__content">
                    <div className="kfm-welcome__eyebrow">
                        Welcome back, {firstName}
                    </div>

                    <h1 className="kfm-welcome__title">Kay Factory Music</h1>

                    <p className="kfm-welcome__lede">
                        Manage your artists, releases, distribution and more —
                        all in one place.
                    </p>
                </div>
            </section>

            {/* Quick actions */}
            <section className="kfm-quick-actions">
                <QuickAction
                    icon="bi-person-plus"
                    label="Add New Artist"
                    to="/artists/new"
                />
                <QuickAction
                    icon="bi-rocket-takeoff"
                    label="Upload Release"
                    to="/releases/new"
                />
                <QuickAction
                    icon="bi-file-earmark-plus"
                    label="Create Contract"
                    to="/contracts/new"
                />
                <QuickAction
                    icon="bi-bar-chart-line"
                    label="View Reports"
                    to="/reports"
                />
            </section>

            {error && (
                <div
                    className="kfm-content alert-danger"
                    style={{ padding: 16 }}
                >
                    {error}{" "}
                    <button
                        type="button"
                        className="btn btn-sm btn-outline-secondary ms-2"
                        onClick={fetchAll}
                    >
                        Retry
                    </button>
                </div>
            )}

            {/* Stat cards — visible to everyone for artists/releases */}
            <section className="kfm-stats">
                <StatCard
                    icon="bi-people"
                    label="Total Artists"
                    value={loading ? "—" : data.artistsTotal}
                    tone="gold"
                />
                <StatCard
                    icon="bi-vinyl"
                    label="Total Releases"
                    value={loading ? "—" : data.releasesTotal}
                    tone="blue"
                />

                {showFinance && (
                    <StatCard
                        icon="bi-currency-dollar"
                        label="Total Revenue"
                        value={loading ? "—" : formatMoney(data.revenueTotal)}
                        tone="green"
                    />
                )}

                <StatCard
                    icon="bi-file-earmark-text"
                    label="Active Contracts"
                    value={loading ? "—" : data.activeContracts}
                    tone="pink"
                />
            </section>

            {/* Two-column row */}
            <section
                className={`kfm-grid ${showFinance ? "kfm-grid--2-1" : "kfm-grid--1-1"}`}
            >
                {/* Recent artists */}
                <div className="kfm-card">
                    <div className="kfm-card__header">
                        <h2 className="kfm-card__title">Recent Artists</h2>
                        <Link to="/artists" className="kfm-card__link">
                            View all →
                        </Link>
                    </div>

                    {loading ? (
                        <div className="text-muted small py-4">Loading…</div>
                    ) : data.recentArtists.length === 0 ? (
                        <div className="text-muted small py-4">
                            No artists yet.
                        </div>
                    ) : (
                        <ul className="kfm-artist-list">
                            {data.recentArtists.map((a) => (
                                <li key={a.id} className="kfm-artist-row">
                                    <div
                                        className="kfm-artist-row__avatar"
                                        style={{ background: "#E4B84C" }}
                                    >
                                        {(a.name || "?")
                                            .charAt(0)
                                            .toUpperCase()}
                                    </div>
                                    <div className="kfm-artist-row__body">
                                        <Link
                                            to={`/artists/${a.id}`}
                                            className="kfm-artist-row__name"
                                        >
                                            {a.name}
                                        </Link>
                                        <div className="kfm-artist-row__handle">
                                            {a.artist_code}
                                        </div>
                                    </div>
                                    <div className="kfm-artist-row__genre">
                                        {a.genre || "—"}
                                    </div>
                                    <span
                                        className={`kfm-badge ${STATUS_CLASS[a.status] || "kfm-badge--muted"}`}
                                    >
                                        {statusLabel(a.status)}
                                    </span>
                                    <div className="kfm-artist-row__joined">
                                        {a.created_at
                                            ? new Date(
                                                  a.created_at,
                                              ).toLocaleDateString()
                                            : "—"}
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>

                {/* Revenue chart — only for finance roles */}
                {showFinance && (
                    <div className="kfm-card">
                        <div className="kfm-card__header">
                            <h2 className="kfm-card__title">
                                Revenue Overview
                            </h2>
                            <span className="kfm-card__meta">
                                Last 6 months
                            </span>
                        </div>

                        {loading ? (
                            <div className="text-muted small py-4">
                                Loading…
                            </div>
                        ) : (
                            <div className="kfm-revenue">
                                <div className="kfm-revenue__total">
                                    {formatMoney(data.revenueTotal)}
                                </div>
                                <div className="kfm-revenue__delta">
                                    Aggregated from revenue entries
                                </div>

                                <div className="kfm-revenue__chart">
                                    {data.revenueMonths.map((d) => (
                                        <div
                                            key={d.month}
                                            className="kfm-revenue__bar-wrap"
                                        >
                                            <div
                                                className="kfm-revenue__bar"
                                                style={{
                                                    height:
                                                        d.value > 0
                                                            ? `${(d.value / maxRev) * 100}%`
                                                            : "2%",
                                                    opacity:
                                                        d.value > 0 ? 1 : 0.2,
                                                }}
                                            />
                                            <div className="kfm-revenue__label">
                                                {d.month}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                )}
            </section>

            {/* Second two-column row */}
            <section className="kfm-grid kfm-grid--1-1">
                {/* Latest releases */}
                <div className="kfm-card">
                    <div className="kfm-card__header">
                        <h2 className="kfm-card__title">Latest Releases</h2>
                        <Link to="/releases" className="kfm-card__link">
                            View all →
                        </Link>
                    </div>

                    {loading ? (
                        <div className="text-muted small py-4">Loading…</div>
                    ) : data.latestReleases.length === 0 ? (
                        <div className="text-muted small py-4">
                            No releases yet.
                        </div>
                    ) : (
                        <ul className="kfm-release-list">
                            {data.latestReleases.map((r) => (
                                <li key={r.id} className="kfm-release-row">
                                    <div className="kfm-release-row__cover">
                                        <i className="bi bi-vinyl-fill"></i>
                                    </div>
                                    <div className="kfm-release-row__body">
                                        <Link
                                            to={`/releases/${r.id}`}
                                            className="kfm-release-row__title"
                                        >
                                            {r.title}
                                        </Link>
                                        <div className="kfm-release-row__meta">
                                            {r.artist?.name || "—"} · {r.type} ·{" "}
                                            {r.release_date || "unscheduled"}
                                        </div>
                                    </div>
                                    <span
                                        className={`kfm-badge ${STATUS_CLASS[r.status] || "kfm-badge--muted"}`}
                                    >
                                        {statusLabel(r.status)}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>

                {/* Activity feed — only for SA + LM */}
                {showAudit ? (
                    <div className="kfm-card">
                        <div className="kfm-card__header">
                            <h2 className="kfm-card__title">Recent Activity</h2>
                            <Link to="/audit-logs" className="kfm-card__link">
                                View all →
                            </Link>
                        </div>

                        {loading ? (
                            <div className="text-muted small py-4">
                                Loading…
                            </div>
                        ) : data.activity.length === 0 ? (
                            <div className="text-muted small py-4">
                                No activity yet.
                            </div>
                        ) : (
                            <div className="kfm-activity-list">
                                {data.activity.map((log) => (
                                    <ActivityItem key={log.id} log={log} />
                                ))}
                            </div>
                        )}
                    </div>
                ) : (
                    <div className="kfm-card">
                        <div className="kfm-card__header">
                            <h2 className="kfm-card__title">Quick Links</h2>
                        </div>
                        <ul className="kfm-artist-list">
                            <li
                                className="kfm-artist-row"
                                style={{ gridTemplateColumns: "1fr" }}
                            >
                                <Link to="/artists" className="kfm-card__link">
                                    Browse all artists →
                                </Link>
                            </li>
                            <li
                                className="kfm-artist-row"
                                style={{ gridTemplateColumns: "1fr" }}
                            >
                                <Link to="/releases" className="kfm-card__link">
                                    Browse all releases →
                                </Link>
                            </li>
                            <li
                                className="kfm-artist-row"
                                style={{ gridTemplateColumns: "1fr" }}
                            >
                                <Link
                                    to="/contracts"
                                    className="kfm-card__link"
                                >
                                    Browse all contracts →
                                </Link>
                            </li>
                        </ul>
                    </div>
                )}
            </section>
        </div>
    );
}
