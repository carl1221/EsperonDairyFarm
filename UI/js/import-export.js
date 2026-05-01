// ============================================================
// js/import-export.js
// Shared Import / Export utility for all record pages.
// Supports: CSV export, Excel export, PDF export, CSV import.
// Usage:
//   ImportExport.addButtons(containerEl, { getData, columns, title, onImport })
// ============================================================

var ImportExport = (function() {

  // ── CSV Export ────────────────────────────────────────────
  function exportCSV(data, columns, filename) {
    var header = columns.map(function(c){ return '"' + c.label + '"'; }).join(',');
    var rows   = data.map(function(row) {
      return columns.map(function(c) {
        var val = row[c.key] !== undefined && row[c.key] !== null ? row[c.key] : '';
        return '"' + String(val).replace(/"/g, '""') + '"';
      }).join(',');
    });
    var csv  = [header].concat(rows).join('\r\n');
    var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    _download(blob, filename + '.csv');
  }

  // ── Excel Export (XLSX via SheetJS CDN) ───────────────────
  function exportExcel(data, columns, filename) {
    _ensureSheetJS(function() {
      var wsData = [columns.map(function(c){ return c.label; })];
      data.forEach(function(row) {
        wsData.push(columns.map(function(c){ return row[c.key] !== undefined ? row[c.key] : ''; }));
      });
      var wb = XLSX.utils.book_new();
      var ws = XLSX.utils.aoa_to_sheet(wsData);
      // Auto column widths
      var colWidths = columns.map(function(c, i) {
        var max = c.label.length;
        data.forEach(function(row) {
          var v = String(row[c.key] || '');
          if (v.length > max) max = v.length;
        });
        return { wch: Math.min(max + 2, 40) };
      });
      ws['!cols'] = colWidths;
      XLSX.utils.book_append_sheet(wb, ws, 'Data');
      XLSX.writeFile(wb, filename + '.xlsx');
    });
  }

  // ── PDF Export (jsPDF + autoTable via CDN) ────────────────
  function exportPDF(data, columns, filename, title) {
    _ensureJsPDF(function() {
      var doc = new jspdf.jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });

      // Header
      doc.setFontSize(16);
      doc.setFont('helvetica', 'bold');
      doc.text(title || filename, 14, 18);
      doc.setFontSize(9);
      doc.setFont('helvetica', 'normal');
      doc.setTextColor(120);
      doc.text('Esperon Dairy Farm  ·  Exported ' + new Date().toLocaleString(), 14, 25);
      doc.setTextColor(0);

      var head = [columns.map(function(c){ return c.label; })];
      var body = data.map(function(row) {
        return columns.map(function(c){ return row[c.key] !== undefined && row[c.key] !== null ? String(row[c.key]) : ''; });
      });

      doc.autoTable({
        head: head,
        body: body,
        startY: 30,
        styles: { fontSize: 8, cellPadding: 3 },
        headStyles: { fillColor: [78, 96, 64], textColor: 255, fontStyle: 'bold' },
        alternateRowStyles: { fillColor: [245, 237, 224] },
        margin: { left: 14, right: 14 },
      });

      doc.save(filename + '.pdf');
    });
  }

  // ── CSV Import ────────────────────────────────────────────
  function importCSV(file, columns, onSuccess) {
    var reader = new FileReader();
    reader.onload = function(e) {
      try {
        var lines  = e.target.result.split(/\r?\n/).filter(function(l){ return l.trim(); });
        if (lines.length < 2) { _toast('CSV file is empty or has no data rows.', 'error'); return; }

        var headers = _parseCSVRow(lines[0]).map(function(h){ return h.trim().toLowerCase(); });
        var records = [];

        for (var i = 1; i < lines.length; i++) {
          var vals = _parseCSVRow(lines[i]);
          var obj  = {};
          columns.forEach(function(col) {
            var idx = headers.indexOf(col.label.toLowerCase());
            if (idx === -1) idx = headers.indexOf(col.key.toLowerCase());
            obj[col.key] = idx >= 0 ? (vals[idx] || '').trim() : '';
          });
          records.push(obj);
        }

        onSuccess(records);
      } catch(err) {
        _toast('Failed to parse CSV: ' + err.message, 'error');
      }
    };
    reader.readAsText(file);
  }

  // ── Build toolbar buttons ─────────────────────────────────
  function addButtons(container, opts) {
    // opts: { getData, columns, title, filename, onImport }
    var wrap = document.createElement('div');
    wrap.style.cssText = 'display:flex;gap:6px;align-items:center;flex-wrap:wrap;';

    // Export dropdown trigger
    var exportBtn = _makeBtn('download', 'Export', 'rgba(255,255,255,.5)', 'var(--border)', 'var(--text)');
    var importBtn = _makeBtn('upload',   'Import CSV', 'rgba(255,255,255,.5)', 'var(--border)', 'var(--text)');

    // Hidden file input for import
    var fileInput = document.createElement('input');
    fileInput.type   = 'file';
    fileInput.accept = '.csv';
    fileInput.style.display = 'none';
    fileInput.addEventListener('change', function() {
      var file = fileInput.files[0];
      if (!file) return;
      fileInput.value = '';
      if (typeof opts.onImport === 'function') {
        importCSV(file, opts.columns, opts.onImport);
      }
    });

    importBtn.addEventListener('click', function() { fileInput.click(); });

    // Export dropdown
    var dropdown = document.createElement('div');
    dropdown.style.cssText = 'position:absolute;top:calc(100% + 4px);right:0;background:#faf6f0;'
      + 'border:1.5px solid var(--border-light);border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,0.12);'
      + 'z-index:9999;min-width:160px;overflow:hidden;display:none;';

    var formats = [
      { icon: 'table_view',    label: 'Export as CSV',   fn: function(d,c,f,t){ exportCSV(d,c,f); } },
      { icon: 'grid_on',       label: 'Export as Excel', fn: function(d,c,f,t){ exportExcel(d,c,f); } },
      { icon: 'picture_as_pdf',label: 'Export as PDF',   fn: function(d,c,f,t){ exportPDF(d,c,f,t); } },
    ];

    formats.forEach(function(fmt) {
      var item = document.createElement('button');
      item.style.cssText = 'display:flex;align-items:center;gap:8px;width:100%;padding:9px 14px;'
        + 'background:none;border:none;cursor:pointer;font-size:0.83rem;font-family:var(--font-sans);'
        + 'color:var(--text);text-align:left;transition:background .12s;';
      item.innerHTML = '<span class="material-symbols-outlined" style="font-size:1rem;color:var(--muted);">' + fmt.icon + '</span>' + fmt.label;
      item.onmouseover = function(){ item.style.background='rgba(78,96,64,0.08)'; };
      item.onmouseout  = function(){ item.style.background=''; };
      item.addEventListener('click', function() {
        dropdown.style.display = 'none';
        var data = typeof opts.getData === 'function' ? opts.getData() : [];
        var fn   = opts.filename || 'export';
        fmt.fn(data, opts.columns, fn, opts.title || fn);
      });
      dropdown.appendChild(item);
    });

    var exportWrap = document.createElement('div');
    exportWrap.style.cssText = 'position:relative;';
    exportWrap.appendChild(exportBtn);
    exportWrap.appendChild(dropdown);

    exportBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
    });
    document.addEventListener('click', function() { dropdown.style.display = 'none'; });

    wrap.appendChild(exportWrap);
    if (typeof opts.onImport === 'function') {
      wrap.appendChild(importBtn);
      wrap.appendChild(fileInput);
    }

    container.appendChild(wrap);
  }

  // ── Private helpers ───────────────────────────────────────
  function _makeBtn(icon, label, bg, border, color) {
    var btn = document.createElement('button');
    btn.style.cssText = 'display:inline-flex;align-items:center;gap:5px;padding:6px 12px;'
      + 'background:' + bg + ';border:1.5px solid ' + border + ';border-radius:8px;'
      + 'cursor:pointer;font-size:0.78rem;font-weight:600;color:' + color + ';'
      + 'font-family:var(--font-sans);transition:opacity .15s;';
    btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:0.9rem;">' + icon + '</span>' + label;
    btn.onmouseover = function(){ btn.style.opacity='0.8'; };
    btn.onmouseout  = function(){ btn.style.opacity='1'; };
    return btn;
  }

  function _parseCSVRow(line) {
    var result = [], cur = '', inQ = false;
    for (var i = 0; i < line.length; i++) {
      var ch = line[i];
      if (ch === '"') {
        if (inQ && line[i+1] === '"') { cur += '"'; i++; }
        else inQ = !inQ;
      } else if (ch === ',' && !inQ) {
        result.push(cur); cur = '';
      } else {
        cur += ch;
      }
    }
    result.push(cur);
    return result;
  }

  function _toast(msg, type) {
    if (typeof UI !== 'undefined') UI.toast(msg, type);
    else console.log('[ImportExport]', msg);
  }

  function _download(blob, filename) {
    var url = URL.createObjectURL(blob);
    var a   = document.createElement('a');
    a.href  = url; a.download = filename;
    document.body.appendChild(a); a.click();
    setTimeout(function(){ document.body.removeChild(a); URL.revokeObjectURL(url); }, 100);
  }

  function _ensureSheetJS(cb) {
    if (typeof XLSX !== 'undefined') { cb(); return; }
    var s = document.createElement('script');
    s.src = 'https://cdn.sheetjs.com/xlsx-0.20.3/package/dist/xlsx.full.min.js';
    s.onload = cb;
    document.head.appendChild(s);
  }

  function _ensureJsPDF(cb) {
    if (typeof jspdf !== 'undefined') { cb(); return; }
    var s1 = document.createElement('script');
    s1.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
    s1.onload = function() {
      var s2 = document.createElement('script');
      s2.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js';
      s2.onload = cb;
      document.head.appendChild(s2);
    };
    document.head.appendChild(s1);
  }

  return { addButtons: addButtons, exportCSV: exportCSV, exportExcel: exportExcel, exportPDF: exportPDF, importCSV: importCSV };
})();
