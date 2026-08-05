/**
 * Genera los .docx de la documentacion tecnica a partir de los .md.
 *
 * Uso:  node docs/tecnica/_build/md-to-docx.js
 *
 * Soporta el subconjunto de Markdown que usan los documentos: encabezados,
 * parrafos, listas con y sin numeracion, tablas GFM, bloques de codigo,
 * citas, reglas horizontales y enfasis en linea (negrita, cursiva, codigo).
 */
const fs = require('fs');
const path = require('path');
const {
  Document, Packer, Paragraph, TextRun, Table, TableRow, TableCell,
  HeadingLevel, AlignmentType, BorderStyle, WidthType, ShadingType,
  PageNumber, Header, Footer, TabStopType,
} = require('docx');

const DOCS_DIR = path.resolve(__dirname, '..');
const AZUL = '1F3864';
const AZUL_CLARO = 'DCE6F1';
const GRIS = '767171';
const GRIS_FONDO = 'F2F2F2';

const ARCHIVOS = [
  {
    md: '01-manual-despliegue-operacion.md',
    docx: '01-manual-despliegue-operacion.docx',
    titulo: 'Manual de Despliegue y Operación',
  },
  {
    md: '02-manual-administrador.md',
    docx: '02-manual-administrador.docx',
    titulo: 'Manual del Administrador',
  },
  {
    md: '03-diccionario-datos-y-rutas.md',
    docx: '03-diccionario-datos-y-rutas.docx',
    titulo: 'Diccionario de Datos y Mapa de Rutas',
  },
];

// --- Enfasis en linea -------------------------------------------------------

/** Convierte el markdown inline de un texto en TextRun[]. */
function runs(texto, base = {}) {
  const out = [];
  // Enlaces [texto](destino) -> nos quedamos con el texto visible.
  let t = texto.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '$1');
  // Escapes de tabla.
  t = t.replace(/\\\|/g, '|');

  const patron = /(\*\*[^*]+\*\*|`[^`]+`|\*[^*]+\*)/g;
  let ultimo = 0;
  let m;
  while ((m = patron.exec(t)) !== null) {
    if (m.index > ultimo) {
      out.push(new TextRun({ text: t.slice(ultimo, m.index), ...base }));
    }
    const tok = m[0];
    if (tok.startsWith('**')) {
      out.push(new TextRun({ text: tok.slice(2, -2), bold: true, ...base }));
    } else if (tok.startsWith('`')) {
      out.push(new TextRun({
        text: tok.slice(1, -1), font: 'Consolas', size: 18, color: 'A31515', ...base,
      }));
    } else {
      out.push(new TextRun({ text: tok.slice(1, -1), italics: true, ...base }));
    }
    ultimo = m.index + tok.length;
  }
  if (ultimo < t.length) out.push(new TextRun({ text: t.slice(ultimo), ...base }));
  if (out.length === 0) out.push(new TextRun({ text: '', ...base }));
  return out;
}

// --- Portada ----------------------------------------------------------------

function portada(titulo) {
  const linea = (texto, opts = {}) => new Paragraph({
    alignment: AlignmentType.CENTER,
    spacing: { after: opts.after ?? 120 },
    children: [new TextRun({
      text: texto,
      bold: opts.bold ?? false,
      size: opts.size ?? 24,
      color: opts.color ?? '000000',
      font: 'Calibri',
    })],
  });

  return [
    new Paragraph({ text: '', spacing: { after: 2400 } }),
    linea('GOBIERNO REGIONAL DE VALPARAÍSO', { bold: true, size: 26, color: GRIS, after: 200 }),
    linea('Plataforma de Procesos Participativos Reglados', { size: 24, color: GRIS, after: 800 }),
    new Paragraph({
      alignment: AlignmentType.CENTER,
      spacing: { after: 200 },
      border: { bottom: { style: BorderStyle.SINGLE, size: 12, color: AZUL, space: 8 } },
      children: [new TextRun({ text: titulo, bold: true, size: 44, color: AZUL, font: 'Calibri' })],
    }),
    linea('Documentación Técnica de Entrega', { size: 24, color: GRIS, after: 1600 }),
    linea('Versión 1.0', { bold: true, size: 24, after: 100 }),
    linea('5 de agosto de 2026', { size: 22, color: GRIS, after: 100 }),
    linea('Elaborado por AWNA', { size: 22, color: GRIS, after: 0 }),
    new Paragraph({ text: '', pageBreakBefore: false }),
  ];
}

// --- Tablas -----------------------------------------------------------------

function celdas(fila) {
  return fila.replace(/^\||\|$/g, '').split(/(?<!\\)\|/).map((c) => c.trim());
}

function tabla(lineas) {
  const encabezado = celdas(lineas[0]);
  const cuerpo = lineas.slice(2).map(celdas);
  const columnas = encabezado.length;

  const fila = (valores, esEncabezado) => new TableRow({
    tableHeader: esEncabezado,
    children: Array.from({ length: columnas }, (_, i) => new TableCell({
      width: { size: Math.floor(100 / columnas), type: WidthType.PERCENTAGE },
      shading: esEncabezado
        ? { type: ShadingType.CLEAR, fill: AZUL_CLARO }
        : undefined,
      margins: { top: 60, bottom: 60, left: 100, right: 100 },
      children: [new Paragraph({
        spacing: { before: 20, after: 20 },
        children: runs(valores[i] ?? '', { size: 18, bold: esEncabezado }),
      })],
    })),
  });

  return new Table({
    width: { size: 100, type: WidthType.PERCENTAGE },
    borders: {
      top: { style: BorderStyle.SINGLE, size: 4, color: 'BFBFBF' },
      bottom: { style: BorderStyle.SINGLE, size: 4, color: 'BFBFBF' },
      left: { style: BorderStyle.SINGLE, size: 4, color: 'BFBFBF' },
      right: { style: BorderStyle.SINGLE, size: 4, color: 'BFBFBF' },
      insideHorizontal: { style: BorderStyle.SINGLE, size: 2, color: 'D9D9D9' },
      insideVertical: { style: BorderStyle.SINGLE, size: 2, color: 'D9D9D9' },
    },
    rows: [fila(encabezado, true), ...cuerpo.map((f) => fila(f, false))],
  });
}

// --- Conversion del cuerpo --------------------------------------------------

function convertir(md) {
  const lineas = md.split(/\r?\n/);
  const hijos = [];
  let i = 0;
  let saltarPrimerH1 = true;

  const esTabla = (n) => /^\s*\|/.test(lineas[n] ?? '')
    && /^\s*\|[\s:|-]+\|\s*$/.test(lineas[n + 1] ?? '');

  while (i < lineas.length) {
    const linea = lineas[i];

    // Bloques de codigo
    if (/^```/.test(linea)) {
      i += 1;
      const buffer = [];
      while (i < lineas.length && !/^```/.test(lineas[i])) {
        buffer.push(lineas[i]);
        i += 1;
      }
      i += 1;
      buffer.forEach((l, idx) => hijos.push(new Paragraph({
        spacing: { before: idx === 0 ? 120 : 0, after: idx === buffer.length - 1 ? 160 : 0 },
        shading: { type: ShadingType.CLEAR, fill: GRIS_FONDO },
        children: [new TextRun({ text: l || ' ', font: 'Consolas', size: 17 })],
      })));
      continue;
    }

    // Tablas
    if (esTabla(i)) {
      const buffer = [];
      while (i < lineas.length && /^\s*\|/.test(lineas[i])) {
        buffer.push(lineas[i].trim());
        i += 1;
      }
      hijos.push(tabla(buffer));
      hijos.push(new Paragraph({ text: '', spacing: { after: 160 } }));
      continue;
    }

    // Regla horizontal
    if (/^---+\s*$/.test(linea)) {
      hijos.push(new Paragraph({
        spacing: { before: 120, after: 120 },
        border: { bottom: { style: BorderStyle.SINGLE, size: 6, color: 'D9D9D9', space: 1 } },
        children: [new TextRun({ text: '' })],
      }));
      i += 1;
      continue;
    }

    // Encabezados
    const h = linea.match(/^(#{1,4})\s+(.*)$/);
    if (h) {
      const nivel = h[1].length;
      const texto = h[2].replace(/\s*#*\s*$/, '');
      if (nivel === 1 && saltarPrimerH1) {
        // El titulo va en la portada; no se repite en el cuerpo.
        saltarPrimerH1 = false;
        i += 1;
        continue;
      }
      const estilos = {
        1: { heading: HeadingLevel.HEADING_1, size: 34, color: AZUL, before: 320, after: 180 },
        2: { heading: HeadingLevel.HEADING_1, size: 30, color: AZUL, before: 300, after: 160 },
        3: { heading: HeadingLevel.HEADING_2, size: 25, color: AZUL, before: 240, after: 120 },
        4: { heading: HeadingLevel.HEADING_3, size: 22, color: GRIS, before: 200, after: 100 },
      }[nivel];
      hijos.push(new Paragraph({
        heading: estilos.heading,
        spacing: { before: estilos.before, after: estilos.after },
        children: [new TextRun({
          text: texto.replace(/\*\*/g, '').replace(/`/g, ''),
          bold: true,
          size: estilos.size,
          color: estilos.color,
          font: 'Calibri',
        })],
      }));
      i += 1;
      continue;
    }

    // Citas
    if (/^>\s?/.test(linea)) {
      const buffer = [];
      while (i < lineas.length && /^>\s?/.test(lineas[i])) {
        buffer.push(lineas[i].replace(/^>\s?/, ''));
        i += 1;
      }
      hijos.push(new Paragraph({
        spacing: { before: 120, after: 160 },
        indent: { left: 360 },
        border: { left: { style: BorderStyle.SINGLE, size: 12, color: AZUL, space: 12 } },
        children: runs(buffer.join(' ').trim(), { size: 20, italics: true }),
      }));
      continue;
    }

    // Listas con numeracion
    const ol = linea.match(/^(\s*)(\d+)\.\s+(.*)$/);
    if (ol) {
      hijos.push(new Paragraph({
        spacing: { after: 60 },
        indent: { left: 360 + ol[1].length * 180, hanging: 260 },
        children: [
          new TextRun({ text: `${ol[2]}.  `, bold: true, size: 21 }),
          ...runs(ol[3], { size: 21 }),
        ],
      }));
      i += 1;
      continue;
    }

    // Listas con vinetas
    const ul = linea.match(/^(\s*)[-*]\s+(.*)$/);
    if (ul) {
      hijos.push(new Paragraph({
        spacing: { after: 60 },
        bullet: { level: Math.min(Math.floor(ul[1].length / 2), 2) },
        children: runs(ul[2], { size: 21 }),
      }));
      i += 1;
      continue;
    }

    // Linea en blanco
    if (/^\s*$/.test(linea)) {
      i += 1;
      continue;
    }

    // Parrafo: acumula lineas hasta el proximo corte.
    const buffer = [];
    while (
      i < lineas.length
      && !/^\s*$/.test(lineas[i])
      && !/^(#{1,4}\s|```|>|---+\s*$)/.test(lineas[i])
      && !/^(\s*)([-*]\s|\d+\.\s)/.test(lineas[i])
      && !/^\s*\|/.test(lineas[i])
    ) {
      buffer.push(lineas[i].trim());
      i += 1;
    }
    if (buffer.length) {
      hijos.push(new Paragraph({
        spacing: { after: 140, line: 276 },
        alignment: AlignmentType.JUSTIFIED,
        children: runs(buffer.join(' '), { size: 21 }),
      }));
    }
  }

  return hijos;
}

// --- Documento --------------------------------------------------------------

function construir(titulo, cuerpo) {
  const pie = new Footer({
    children: [new Paragraph({
      tabStops: [{ type: TabStopType.RIGHT, position: 9020 }],
      children: [
        new TextRun({ text: `${titulo} · GORE Valparaíso · AWNA`, size: 16, color: GRIS }),
        new TextRun({ text: '\t', size: 16 }),
        new TextRun({ children: ['Página ', PageNumber.CURRENT], size: 16, color: GRIS }),
      ],
    })],
  });

  const encabezado = new Header({
    children: [new Paragraph({
      alignment: AlignmentType.RIGHT,
      border: { bottom: { style: BorderStyle.SINGLE, size: 4, color: 'D9D9D9', space: 4 } },
      children: [new TextRun({
        text: 'Plataforma de Procesos Participativos Reglados',
        size: 16,
        color: GRIS,
      })],
    })],
  });

  return new Document({
    creator: 'AWNA',
    title: `${titulo} — GORE Valparaíso`,
    description: 'Documentación técnica de entrega',
    styles: { default: { document: { run: { font: 'Calibri', size: 21 } } } },
    sections: [
      {
        properties: { page: { margin: { top: 1440, bottom: 1440, left: 1134, right: 1134 } } },
        children: portada(titulo),
      },
      {
        properties: { page: { margin: { top: 1134, bottom: 1134, left: 1134, right: 1134 } } },
        headers: { default: encabezado },
        footers: { default: pie },
        children: cuerpo,
      },
    ],
  });
}

// --- Main -------------------------------------------------------------------

(async () => {
  for (const archivo of ARCHIVOS) {
    const origen = path.join(DOCS_DIR, archivo.md);
    const destino = path.join(DOCS_DIR, archivo.docx);
    const md = fs.readFileSync(origen, 'utf8');
    const doc = construir(archivo.titulo, convertir(md));
    fs.writeFileSync(destino, await Packer.toBuffer(doc));
    const kb = (fs.statSync(destino).size / 1024).toFixed(1);
    console.log(`OK  ${archivo.docx}  (${kb} KB)`);
  }
})().catch((e) => {
  console.error(e);
  process.exit(1);
});
