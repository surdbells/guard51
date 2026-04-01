/**
 * Export data as CSV file download
 */
export function exportToCsv(filename: string, rows: any[], columns?: { key: string; label: string }[]): void {
  if (!rows.length) return;

  // Auto-detect columns from first row if not provided
  const cols = columns || Object.keys(rows[0]).map(k => ({ key: k, label: k.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) }));

  const header = cols.map(c => `"${c.label}"`).join(',');
  const body = rows.map(row =>
    cols.map(c => {
      const val = row[c.key];
      if (val === null || val === undefined) return '""';
      const str = String(val).replace(/"/g, '""');
      return `"${str}"`;
    }).join(',')
  ).join('\n');

  const csv = '\uFEFF' + header + '\n' + body; // BOM for Excel
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = `${filename}-${new Date().toISOString().slice(0, 10)}.csv`;
  link.click();
  URL.revokeObjectURL(url);
}
