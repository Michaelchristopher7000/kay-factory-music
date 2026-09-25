import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { createIdleTimer } from '../../resources/js/staff/idleTimeout';

describe('createIdleTimer', () => {
  beforeEach(() => {
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it('throws if warningMs >= logoutMs', () => {
    expect(() =>
      createIdleTimer({ warningMs: 1000, logoutMs: 1000 })
    ).toThrow();
    expect(() =>
      createIdleTimer({ warningMs: 2000, logoutMs: 1000 })
    ).toThrow();
  });

  it('fires onWarn at the warning threshold', () => {
    const onWarn = vi.fn();
    const onLogout = vi.fn();

    createIdleTimer({
      warningMs: 25 * 60_000,
      logoutMs: 30 * 60_000,
      onWarn,
      onLogout,
    });

    vi.advanceTimersByTime(24 * 60_000);
    expect(onWarn).not.toHaveBeenCalled();

    vi.advanceTimersByTime(60_000);
    expect(onWarn).toHaveBeenCalledTimes(1);
    expect(onLogout).not.toHaveBeenCalled();
  });

  it('fires onLogout at the logout threshold', () => {
    const onWarn = vi.fn();
    const onLogout = vi.fn();

    createIdleTimer({
      warningMs: 25 * 60_000,
      logoutMs: 30 * 60_000,
      onWarn,
      onLogout,
    });

    vi.advanceTimersByTime(30 * 60_000);
    expect(onWarn).toHaveBeenCalledTimes(1);
    expect(onLogout).toHaveBeenCalledTimes(1);
  });

  it('forced reset pushes the deadline forward', () => {
    const onWarn = vi.fn();
    const onLogout = vi.fn();

    const timer = createIdleTimer({
      warningMs: 25 * 60_000,
      logoutMs: 30 * 60_000,
      onWarn,
      onLogout,
      throttleMs: 0,
    });

    vi.advanceTimersByTime(10 * 60_000);
    timer.reset({ force: true });

    vi.advanceTimersByTime(20 * 60_000);
    expect(onWarn).not.toHaveBeenCalled();

    vi.advanceTimersByTime(5 * 60_000);
    expect(onWarn).toHaveBeenCalledTimes(1);
  });

  it('throttles ordinary reset() calls', () => {
    const onWarn = vi.fn();
    const onLogout = vi.fn();

    const timer = createIdleTimer({
      warningMs: 25 * 60_000,
      logoutMs: 30 * 60_000,
      onWarn,
      onLogout,
      throttleMs: 1000,
    });

    expect(timer.reset()).toBe(true);
    expect(timer.reset()).toBe(false);
    expect(timer.reset()).toBe(false);

    vi.advanceTimersByTime(1000);
    expect(timer.reset()).toBe(true);
  });

  it('ignores ordinary activity while the warning is showing', () => {
    const onWarn = vi.fn();
    const onLogout = vi.fn();

    const timer = createIdleTimer({
      warningMs: 25 * 60_000,
      logoutMs: 30 * 60_000,
      onWarn,
      onLogout,
      throttleMs: 0,
    });

    vi.advanceTimersByTime(25 * 60_000);
    expect(onWarn).toHaveBeenCalledTimes(1);
    expect(timer.isWarned()).toBe(true);

    expect(timer.reset()).toBe(false);
    expect(timer.isWarned()).toBe(true);
  });

  it('forced reset clears the warning state', () => {
    const onWarn = vi.fn();
    const onLogout = vi.fn();

    const timer = createIdleTimer({
      warningMs: 25 * 60_000,
      logoutMs: 30 * 60_000,
      onWarn,
      onLogout,
      throttleMs: 0,
    });

    vi.advanceTimersByTime(25 * 60_000);
    expect(timer.isWarned()).toBe(true);

    timer.reset({ force: true });
    expect(timer.isWarned()).toBe(false);

    vi.advanceTimersByTime(4 * 60_000);
    expect(onLogout).not.toHaveBeenCalled();
  });

  it('destroy() prevents callbacks from firing', () => {
    const onWarn = vi.fn();
    const onLogout = vi.fn();

    const timer = createIdleTimer({
      warningMs: 1000,
      logoutMs: 2000,
      onWarn,
      onLogout,
    });

    timer.destroy();

    vi.advanceTimersByTime(10_000);
    expect(onWarn).not.toHaveBeenCalled();
    expect(onLogout).not.toHaveBeenCalled();
    expect(timer.isDestroyed()).toBe(true);
  });

  it('reset() after destroy() is a no-op', () => {
    const timer = createIdleTimer({
      warningMs: 1000,
      logoutMs: 2000,
    });

    timer.destroy();
    expect(timer.reset({ force: true })).toBe(false);
  });
});