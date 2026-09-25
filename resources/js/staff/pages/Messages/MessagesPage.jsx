import { useEffect, useRef, useState, useCallback } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import api from '../../api';
import { useAuth } from '../../auth';

const formatTime = (d) => {
  if (!d) return '';
  const date = new Date(d);
  const now = new Date();
  const isToday = date.toDateString() === now.toDateString();
  if (isToday) {
    return date.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
  }
  return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
};

export default function MessagesPage() {
  const { user } = useAuth();
  const { conversationId } = useParams();
  const navigate = useNavigate();

  const [conversations, setConversations] = useState([]);
  const [activeConversation, setActiveConversation] = useState(null);
  const [messages, setMessages] = useState([]);
  const [recipients, setRecipients] = useState([]);
  const [loading, setLoading] = useState(true);
  const [loadingThread, setLoadingThread] = useState(false);
  const [sending, setSending] = useState(false);
  const [draft, setDraft] = useState('');
  const [error, setError] = useState('');
  const [showNewModal, setShowNewModal] = useState(false);

  const messagesEndRef = useRef(null);

  // Load conversations list
  const loadConversations = useCallback(async () => {
    try {
      const { data } = await api.get('/staff-messages/conversations');
      setConversations(data.data || []);
    } catch (err) {
      if (err.response?.status === 401) return;
      setError('Could not load conversations.');
    } finally {
      setLoading(false);
    }
  }, []);

  // Load recipients (for new conversation modal)
  const loadRecipients = useCallback(async () => {
    try {
      const { data } = await api.get('/staff-messages/recipients');
      setRecipients(data.data || []);
    } catch {
      // silent
    }
  }, []);

  useEffect(() => {
    loadConversations();
    loadRecipients();
  }, [loadConversations, loadRecipients]);

  // Poll conversations every 15s while the page is open
  useEffect(() => {
    const t = setInterval(loadConversations, 15000);
    return () => clearInterval(t);
  }, [loadConversations]);

  // Load thread when a conversation is selected
  useEffect(() => {
    if (!conversationId) {
      setActiveConversation(null);
      setMessages([]);
      return;
    }

    let alive = true;

    (async () => {
      setLoadingThread(true);
      try {
        const { data } = await api.get(`/staff-messages/conversations/${conversationId}`);
        if (!alive) return;
        setActiveConversation(data.data.conversation);
        setMessages(data.data.messages || []);
        // Refresh list to update unread counts
        loadConversations();
      } catch (err) {
        if (!alive) return;
        if (err.response?.status === 403) {
          setError('You do not have access to this conversation.');
          navigate('/messages');
        } else if (err.response?.status === 404) {
          setError('Conversation not found.');
          navigate('/messages');
        }
      } finally {
        if (alive) setLoadingThread(false);
      }
    })();

    return () => { alive = false; };
  }, [conversationId, loadConversations, navigate]);

  // Auto-scroll to bottom when messages change
  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [messages.length]);

  // Poll active thread every 8s (only if not currently sending)
  useEffect(() => {
    if (!conversationId || sending) return;

    const t = setInterval(async () => {
      try {
        const { data } = await api.get(`/staff-messages/conversations/${conversationId}`);
        setMessages(data.data.messages || []);
      } catch {
        // silent
      }
    }, 8000);

    return () => clearInterval(t);
  }, [conversationId, sending]);

  const send = async (e) => {
    e.preventDefault();
    if (!draft.trim() || sending || !conversationId) return;

    setSending(true);
    const body = draft.trim();
    setDraft('');

    try {
      const { data } = await api.post(
        `/staff-messages/conversations/${conversationId}/messages`,
        { body },
      );
      setMessages((prev) => [...prev, data.data]);
      loadConversations();
    } catch {
      setDraft(body);
      setError('Could not send message.');
    } finally {
      setSending(false);
    }
  };

  const startConversation = async (recipientId, firstMessage) => {
    try {
      const { data } = await api.post('/staff-messages/conversations', {
        user_id: recipientId,
        body: firstMessage,
      });
      setShowNewModal(false);
      navigate(`/messages/${data.data.conversation_id}`);
    } catch {
      setError('Could not start conversation.');
    }
  };

  return (
    <div className={`kfm-messages ${conversationId ? 'has-active-thread' : ''}`}>
      <div className="kfm-messages__sidebar">
        <div className="kfm-messages__sidebar-head">
          <h5 className="mb-0">Messages</h5>
          <button
            type="button"
            className="btn btn-sm btn-dark"
            onClick={() => setShowNewModal(true)}
          >
            <i className="bi bi-pencil-square me-1"></i> New
          </button>
        </div>

        {loading && (
          <div className="text-center py-4 text-muted small">
            <div className="spinner-border spinner-border-sm me-2"></div>
            Loading…
          </div>
        )}

        {!loading && conversations.length === 0 && (
          <div className="text-center py-5 text-muted small">
            <i className="bi bi-chat-dots d-block mb-2" style={{ fontSize: '2rem', opacity: 0.4 }}></i>
            No conversations yet.
            <div className="mt-2">Click <strong>New</strong> to start one.</div>
          </div>
        )}

        <div className="kfm-messages__conversation-list">
          {conversations.map((c) => {
            const isActive = String(c.id) === String(conversationId);
            return (
              <button
                key={c.id}
                type="button"
                className={`kfm-messages__conversation ${isActive ? 'is-active' : ''} ${c.unread_count > 0 ? 'has-unread' : ''}`}
                onClick={() => navigate(`/messages/${c.id}`)}
              >
                <div className="kfm-messages__avatar">
                  {c.other_user?.avatar_url ? (
                    <img src={c.other_user.avatar_url} alt="" />
                  ) : (
                    <span>{(c.other_user?.name || 'S').charAt(0).toUpperCase()}</span>
                  )}
                </div>
                <div className="kfm-messages__conversation-body">
                  <div className="kfm-messages__conversation-head">
                    <div className="kfm-messages__conversation-name">
                      {c.other_user?.name || 'Unknown'}
                    </div>
                    <div className="kfm-messages__conversation-time">
                      {formatTime(c.last_message_at)}
                    </div>
                  </div>
                  <div className="kfm-messages__conversation-preview">
                    {c.last_message?.body || 'No messages yet'}
                  </div>
                </div>
                {c.unread_count > 0 && (
                  <span className="kfm-messages__unread-badge">
                    {c.unread_count > 99 ? '99+' : c.unread_count}
                  </span>
                )}
              </button>
            );
          })}
        </div>
      </div>

      <div className="kfm-messages__thread">
        {!conversationId && (
          <div className="kfm-messages__empty">
            <i className="bi bi-chat-dots"></i>
            <div>Select a conversation to view messages.</div>
          </div>
        )}

        {conversationId && loadingThread && (
          <div className="kfm-messages__empty">
            <div className="spinner-border spinner-border-sm"></div>
          </div>
        )}

        {conversationId && !loadingThread && activeConversation && (
          <>
            <div className="kfm-messages__thread-head">
              <button
                type="button"
                className="kfm-messages__back-btn"
                onClick={() => navigate('/messages')}
                aria-label="Back to conversations"
              >
                <i className="bi bi-arrow-left"></i>
              </button>

              <div className="kfm-messages__avatar">
                {activeConversation.other_user?.avatar_url ? (
                  <img src={activeConversation.other_user.avatar_url} alt="" />
                ) : (
                  <span>{(activeConversation.other_user?.name || 'S').charAt(0).toUpperCase()}</span>
                )}
              </div>
              <div>
                <div className="fw-semibold">{activeConversation.other_user?.name || 'Unknown'}</div>
                {activeConversation.other_user?.role && (
                  <div className="text-muted small">{activeConversation.other_user.role}</div>
                )}
              </div>
            </div>

            <div className="kfm-messages__thread-body">
              {messages.length === 0 && (
                <div className="text-center py-5 text-muted small">
                  No messages yet. Say hi 👋
                </div>
              )}

              {messages.map((m) => {
                const isMine = m.sender?.id === user?.id;
                return (
                  <div
                    key={m.id}
                    className={`kfm-messages__bubble ${isMine ? 'is-mine' : 'is-theirs'}`}
                  >
                    <div className="kfm-messages__bubble-body">{m.body}</div>
                    <div className="kfm-messages__bubble-time">
                      {formatTime(m.created_at)}
                    </div>
                  </div>
                );
              })}

              <div ref={messagesEndRef} />
            </div>

            <form className="kfm-messages__composer" onSubmit={send}>
              <textarea
                rows={1}
                className="form-control"
                placeholder="Type a message…"
                value={draft}
                onChange={(e) => setDraft(e.target.value)}
                onKeyDown={(e) => {
                  if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    send(e);
                  }
                }}
                disabled={sending}
              />
              <button
                type="submit"
                className="btn btn-dark"
                disabled={sending || !draft.trim()}
              >
                {sending ? (
                  <span className="spinner-border spinner-border-sm"></span>
                ) : (
                  <><i className="bi bi-send me-1"></i> Send</>
                )}
              </button>
            </form>
          </>
        )}
      </div>

      {showNewModal && (
        <NewConversationModal
          recipients={recipients}
          currentUserId={user?.id}
          onClose={() => setShowNewModal(false)}
          onStart={startConversation}
        />
      )}

      {error && (
        <div
          className="position-fixed bottom-0 end-0 m-3 alert alert-danger"
          style={{ zIndex: 2000 }}
          onClick={() => setError('')}
        >
          {error}
        </div>
      )}
    </div>
  );
}

function NewConversationModal({ recipients, currentUserId, onClose, onStart }) {
  const [selected, setSelected] = useState(null);
  const [firstMessage, setFirstMessage] = useState('');
  const [search, setSearch] = useState('');
  const [submitting, setSubmitting] = useState(false);

  const filtered = recipients.filter((r) =>
    r.name.toLowerCase().includes(search.toLowerCase()) ||
    r.email.toLowerCase().includes(search.toLowerCase()),
  );

  const submit = async (e) => {
    e.preventDefault();
    if (!selected || !firstMessage.trim()) return;
    setSubmitting(true);
    try {
      await onStart(selected.id, firstMessage.trim());
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div
      className="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center"
      style={{ background: 'rgba(0,0,0,0.6)', zIndex: 1050 }}
      onClick={onClose}
    >
      <div
        className="bg-white rounded shadow-lg"
        style={{ width: 500, maxWidth: '92vw', maxHeight: '90vh', overflow: 'auto' }}
        onClick={(e) => e.stopPropagation()}
      >
        <div className="p-3 border-bottom d-flex justify-content-between align-items-center">
          <strong>New Conversation</strong>
          <button type="button" className="btn-close" onClick={onClose}></button>
        </div>

        <form onSubmit={submit}>
          <div className="p-3">
            {!selected ? (
              <>
                <label className="form-label small text-muted">Choose recipient</label>
                <input
                  type="text"
                  className="form-control mb-3"
                  placeholder="Search staff…"
                  value={search}
                  onChange={(e) => setSearch(e.target.value)}
                  autoFocus
                />
                <div style={{ maxHeight: 300, overflowY: 'auto' }}>
                  {filtered.length === 0 && (
                    <div className="text-muted small text-center py-3">No staff found.</div>
                  )}
                  {filtered.map((r) => (
                    <button
                      key={r.id}
                      type="button"
                      className="btn btn-light w-100 text-start mb-1 d-flex align-items-center gap-2"
                      onClick={() => setSelected(r)}
                    >
                      <div className="kfm-messages__avatar kfm-messages__avatar--sm">
                        {r.avatar_url ? (
                          <img src={r.avatar_url} alt="" />
                        ) : (
                          <span>{r.name.charAt(0).toUpperCase()}</span>
                        )}
                      </div>
                      <div>
                        <div className="fw-semibold">{r.name}</div>
                        <div className="text-muted small">{r.role || r.email}</div>
                      </div>
                    </button>
                  ))}
                </div>
              </>
            ) : (
              <>
                <div className="d-flex align-items-center gap-2 mb-3 p-2 border rounded">
                  <div className="kfm-messages__avatar kfm-messages__avatar--sm">
                    {selected.avatar_url ? (
                      <img src={selected.avatar_url} alt="" />
                    ) : (
                      <span>{selected.name.charAt(0).toUpperCase()}</span>
                    )}
                  </div>
                  <div className="flex-grow-1">
                    <div className="fw-semibold">{selected.name}</div>
                    <div className="text-muted small">{selected.role || selected.email}</div>
                  </div>
                  <button
                    type="button"
                    className="btn btn-sm btn-link"
                    onClick={() => setSelected(null)}
                  >
                    Change
                  </button>
                </div>

                <label className="form-label small text-muted">First message</label>
                <textarea
                  rows={4}
                  className="form-control"
                  value={firstMessage}
                  onChange={(e) => setFirstMessage(e.target.value)}
                  required
                  autoFocus
                />
              </>
            )}
          </div>

          <div className="p-3 border-top d-flex justify-content-end gap-2">
            <button type="button" className="btn btn-outline-secondary" onClick={onClose}>
              Cancel
            </button>
            <button
              type="submit"
              className="btn btn-dark"
              disabled={!selected || !firstMessage.trim() || submitting}
            >
              {submitting ? 'Sending…' : 'Send & Open'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}