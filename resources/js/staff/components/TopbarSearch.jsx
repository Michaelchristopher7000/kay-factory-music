import { useEffect, useRef, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { searchAll, KIND_META } from '../searchApi';

export default function TopbarSearch() {
  const navigate = useNavigate();
  const wrapperRef = useRef(null);
  const inputRef = useRef(null);

  const [query, setQuery] = useState('');
  const [results, setResults] = useState([]);
  const [loading, setLoading] = useState(false);
  const [open, setOpen] = useState(false);
  const [highlight, setHighlight] = useState(0);

  // Close on outside click
  useEffect(() => {
    const onClick = (e) => {
      if (!wrapperRef.current) return;
      if (!wrapperRef.current.contains(e.target)) setOpen(false);
    };
    document.addEventListener('mousedown', onClick);
    return () => document.removeEventListener('mousedown', onClick);
  }, []);

  // Debounced search
  useEffect(() => {
    if (query.trim().length < 2) {
      setResults([]);
      setLoading(false);
      return;
    }

    setLoading(true);
    let alive = true;

    const t = setTimeout(async () => {
      const r = await searchAll(query, 4);
      if (alive) {
        setResults(r);
        setHighlight(0);
        setLoading(false);
      }
    }, 300);

    return () => {
      alive = false;
      clearTimeout(t);
    };
  }, [query]);

  // Global keyboard shortcut — press / to focus search
  useEffect(() => {
    const onKey = (e) => {
      if (
        e.key === '/' &&
        !['INPUT', 'TEXTAREA'].includes(document.activeElement?.tagName) &&
        !e.metaKey &&
        !e.ctrlKey
      ) {
        e.preventDefault();
        inputRef.current?.focus();
      }
    };
    document.addEventListener('keydown', onKey);
    return () => document.removeEventListener('keydown', onKey);
  }, []);

  const goTo = (item) => {
    navigate(item.url);
    setOpen(false);
    setQuery('');
    inputRef.current?.blur();
  };

  const onKeyDown = (e) => {
    if (!open || results.length === 0) return;

    if (e.key === 'ArrowDown') {
      e.preventDefault();
      setHighlight((h) => (h + 1) % results.length);
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      setHighlight((h) => (h - 1 + results.length) % results.length);
    } else if (e.key === 'Enter') {
      e.preventDefault();
      goTo(results[highlight]);
    } else if (e.key === 'Escape') {
      setOpen(false);
      inputRef.current?.blur();
    }
  };

  const showDropdown = open && query.trim().length >= 2;

  return (
    <div className="kfm-search" ref={wrapperRef}>
      <i className="bi bi-search kfm-search__icon"></i>
      <input
        ref={inputRef}
        type="text"
        className="kfm-search__input"
        placeholder="Search artists, releases, contracts, tracks…"
        value={query}
        onChange={(e) => {
          setQuery(e.target.value);
          setOpen(true);
        }}
        onFocus={() => setOpen(true)}
        onKeyDown={onKeyDown}
        autoComplete="off"
        spellCheck={false}
      />
      {query && (
        <button
          type="button"
          className="kfm-search__clear"
          onClick={() => { setQuery(''); inputRef.current?.focus(); }}
          aria-label="Clear search"
        >
          <i className="bi bi-x-lg"></i>
        </button>
      )}

      {showDropdown && (
        <div className="kfm-search__panel">
          {loading && (
            <div className="kfm-search__state">
              <div className="spinner-border spinner-border-sm me-2"></div>
              Searching…
            </div>
          )}

          {!loading && results.length === 0 && (
            <div className="kfm-search__state">
              <i className="bi bi-search me-2"></i>
              No results for &ldquo;{query}&rdquo;
            </div>
          )}

          {!loading && results.length > 0 && (
            <ul className="kfm-search__list">
              {results.map((item, i) => {
                const meta = KIND_META[item.kind] || { label: '', icon: 'bi-circle' };
                return (
                  <li key={`${item.kind}-${item.id}`}>
                    <button
                      type="button"
                      className={`kfm-search__item ${i === highlight ? 'is-active' : ''}`}
                      onMouseEnter={() => setHighlight(i)}
                      onClick={() => goTo(item)}
                    >
                      <span className="kfm-search__item-icon">
                        <i className={`bi ${meta.icon}`}></i>
                      </span>
                      <span className="kfm-search__item-body">
                        <span className="kfm-search__item-title">{item.title}</span>
                        <span className="kfm-search__item-sub">
                          {meta.label}
                          {item.subtitle ? ` · ${item.subtitle}` : ''}
                          {item.code ? ` · ${item.code}` : ''}
                        </span>
                      </span>
                      <span className="kfm-search__item-arrow">
                        <i className="bi bi-arrow-return-left"></i>
                      </span>
                    </button>
                  </li>
                );
              })}
            </ul>
          )}

          <div className="kfm-search__footer">
            <span><kbd>↑</kbd> <kbd>↓</kbd> navigate</span>
            <span><kbd>Enter</kbd> open</span>
            <span><kbd>Esc</kbd> close</span>
            <span className="kfm-search__footer-hint">Press <kbd>/</kbd> to focus</span>
          </div>
        </div>
      )}
    </div>
  );
}