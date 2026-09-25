import { useRef, useState } from 'react';

const formatBytes = (bytes) => {
  if (!bytes) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return `${(bytes / Math.pow(k, i)).toFixed(1)} ${sizes[i]}`;
};

export default function FileDrop({
  label,
  accept,
  maxSize,
  file,
  onFile,
  hint,
}) {
  const [dragging, setDragging] = useState(false);
  const [localError, setLocalError] = useState('');
  const inputRef = useRef(null);

  const validate = (f) => {
    if (!f) return '';
    if (maxSize && f.size > maxSize) {
      return `File too large (${formatBytes(f.size)}). Max is ${formatBytes(maxSize)}.`;
    }
    return '';
  };

  const handle = (f) => {
    const err = validate(f);
    setLocalError(err);
    if (!err) onFile(f);
  };

  const onDrop = (e) => {
    e.preventDefault();
    setDragging(false);
    const f = e.dataTransfer.files?.[0];
    if (f) handle(f);
  };

  const onPick = (e) => {
    const f = e.target.files?.[0];
    if (f) handle(f);
  };

  const clear = (e) => {
    e.stopPropagation();
    onFile(null);
    setLocalError('');
    if (inputRef.current) inputRef.current.value = '';
  };

  return (
    <div className="kfm-filedrop">
      <label className="kfm-form__label">
        <span>{label}</span>
      </label>

      <div
        className={`kfm-filedrop__zone ${dragging ? 'is-dragging' : ''} ${file ? 'has-file' : ''}`}
        onDragOver={(e) => { e.preventDefault(); setDragging(true); }}
        onDragLeave={() => setDragging(false)}
        onDrop={onDrop}
        onClick={() => inputRef.current?.click()}
      >
        <input
          ref={inputRef}
          type="file"
          accept={accept}
          onChange={onPick}
          className="kfm-filedrop__input"
        />

        {!file && (
          <>
            <i className="bi bi-cloud-upload kfm-filedrop__icon" />
            <div className="kfm-filedrop__primary">
              Drag &amp; drop or <span>browse</span>
            </div>
            {hint && <div className="kfm-filedrop__hint">{hint}</div>}
          </>
        )}

        {file && (
          <div className="kfm-filedrop__file">
            <i className="bi bi-file-earmark-check kfm-filedrop__file-icon" />
            <div className="kfm-filedrop__file-info">
              <div className="kfm-filedrop__file-name" title={file.name}>
                {file.name}
              </div>
              <div className="kfm-filedrop__file-size">
                {formatBytes(file.size)}
              </div>
            </div>
            <button
              type="button"
              className="kfm-filedrop__clear"
              onClick={clear}
              aria-label="Remove file"
            >
              <i className="bi bi-x-lg" />
            </button>
          </div>
        )}
      </div>

      {localError && <div className="kfm-filedrop__error">{localError}</div>}
    </div>
  );
}