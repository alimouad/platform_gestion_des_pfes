export async function exportPdf(elementId, filename = 'export.pdf') {
  const html2pdf = (await import('html2pdf.js')).default
  const el = document.getElementById(elementId)
  if (!el) return
  html2pdf(el, {
    margin: 10,
    filename,
    image: { type: 'jpeg', quality: 0.98 },
    html2canvas: { scale: 2, useCORS: true },
    jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
  })
}
