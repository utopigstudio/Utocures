export function formatRecordedDuration(minutes: string | number | null | undefined): string {
  if (minutes === null || minutes === undefined) return '--:--'

  const total = Math.max(0, Math.floor(Number(minutes)))
  if (!Number.isFinite(total)) return '--:--'

  return `${Math.floor(total / 60).toString().padStart(2, '0')}:${(total % 60).toString().padStart(2, '0')}`
}
