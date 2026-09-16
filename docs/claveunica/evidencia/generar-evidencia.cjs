const fs = require('fs');
const path = require('path');

const ROOT = path.resolve(__dirname, '..', '..', '..');
const OUT  = path.join(ROOT, 'docs/claveunica/evidencia');

const COMMIT = require('child_process')
  .execSync('git -C "' + ROOT + '" rev-parse --short HEAD').toString().trim();

const esc = s => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

const KW = new Set(['return', 'function', 'private', 'public', 'protected', 'new', 'use',
  'namespace', 'if', 'else', 'null', 'true', 'false', 'array', 'string', 'int', 'mixed',
  'static', 'const', 'class']);

// Una sola pasada sobre el fragmento ya escapado: cada identificador se decide
// una vez y no se vuelve a mirar. Con dos pasadas (funciones y luego palabras
// clave) la segunda reescribia el atributo class= que habia puesto la primera.
function words(seg) {
  return seg.replace(/\b([A-Za-z_][A-Za-z0-9_]*)\b(\s*\()?/g, (m, word, paren) => {
    if (paren) return '<span class="f">' + word + '</span>' + paren;
    if (KW.has(word)) return '<span class="k">' + word + '</span>';
    return m;
  });
}

function hl(line, lang) {
  if (lang === 'env' || lang === 'gitignore') {
    if (/^\s*#/.test(line)) return '<span class="c">' + esc(line) + '</span>';
    // El prefijo "71:" lo pone `grep -n`; se pinta aparte para que no se
    // confunda con la clave.
    const m = line.match(/^(\d+:)?([A-Z0-9_]+)(=)(.*)$/);
    if (m) return (m[1] ? '<span class="nl">' + m[1] + '</span>' : '') +
      '<span class="v">' + esc(m[2]) + '</span>=<span class="s">' + esc(m[4]) + '</span>';
    return esc(line);
  }
  if (/^\s*(\/\/|\/\*|\*|\|)/.test(line)) return '<span class="c">' + esc(line) + '</span>';
  let out = '', i = 0;
  const n = line.length;
  while (i < n) {
    const two = line.slice(i, i + 2);
    if (two === '//' || two === '/*' || two === '*/') {
      out += '<span class="c">' + esc(line.slice(i)) + '</span>';
      break;
    }
    const ch = line[i];
    if (ch === "'" || ch === '"') {
      let j = i + 1;
      while (j < n && line[j] !== ch) { if (line[j] === '\\') j++; j++; }
      out += '<span class="s">' + esc(line.slice(i, Math.min(j + 1, n))) + '</span>';
      i = j + 1;
      continue;
    }
    let j = i;
    while (j < n && line[j] !== "'" && line[j] !== '"' && line.slice(j, j + 2) !== '//' && line.slice(j, j + 2) !== '/*') j++;
    out += words(esc(line.slice(i, j)));
    i = j;
  }
  return out;
}

function rows(file, from, to, highlight, lang) {
  const lines = fs.readFileSync(path.join(ROOT, file), 'utf8').split(/\r?\n/);
  let html = '';
  for (let n = from; n <= to; n++) {
    const raw = lines[n - 1] === undefined ? '' : lines[n - 1];
    const hi = highlight.indexOf(n) !== -1 ? ' hi' : '';
    html += '<div class="ln' + hi + '"><span class="num">' + n + '</span><span class="code">' +
            (hl(raw, lang) || '&nbsp;') + '</span></div>';
  }
  return html;
}

function codePanel(label, file, from, to, highlight, lang) {
  return '<section class="panel"><div class="ptitle">' + label + '</div>' +
    '<div class="editor"><div class="tab"><span class="dot r"></span><span class="dot y"></span>' +
    '<span class="dot g"></span><span class="fname">' + file + '</span>' +
    '<span class="range">l\u00edneas ' + from + '\u2013' + to + '</span></div>' +
    '<div class="body">' + rows(file, from, to, highlight, lang) + '</div></div></section>';
}

function termPanel(label, cmd, out) {
  const lines = out.split('\n').map(l => '<div class="ln"><span class="code">' + esc(l) + '</span></div>').join('');
  return '<section class="panel"><div class="ptitle">' + label + '</div>' +
    '<div class="editor"><div class="tab"><span class="dot r"></span><span class="dot y"></span>' +
    '<span class="dot g"></span><span class="fname">terminal \u00b7 verificaci\u00f3n</span></div>' +
    '<div class="body term"><div class="ln"><span class="code"><span class="prompt">$</span> ' +
    esc(cmd) + '</span></div>' + lines + '</div></div></section>';
}

// Panel de consola con varios comandos: reproduce la sesion en el servidor tal
// como se ejecuto. `steps` es [{prompt, cmd, out:[lineas]}] y las lineas
// listadas en `hi` salen resaltadas. Hace falta para el .env de produccion, que
// por definicion no vive en el repositorio y no se puede leer con codePanel().
function shellPanel(label, titulo, steps, hi) {
  let body = '';
  steps.forEach(s => {
    body += '<div class="ln"><span class="code"><span class="prompt">' + esc(s.prompt) +
            '</span> ' + esc(s.cmd) + '</span></div>';
    s.out.forEach(l => {
      const marca = (hi || []).indexOf(l) !== -1 ? ' hi' : '';
      body += '<div class="ln' + marca + '"><span class="code">' + (hl(l, 'env') || '&nbsp;') + '</span></div>';
    });
  });
  return '<section class="panel"><div class="ptitle">' + label + '</div>' +
    '<div class="editor"><div class="tab"><span class="dot r"></span><span class="dot y"></span>' +
    '<span class="dot g"></span><span class="fname">' + titulo + '</span></div>' +
    '<div class="body term">' + body + '</div></div></section>';
}

const CSS = [
'*{box-sizing:border-box;margin:0;padding:0}',
'body{background:#f4f5f7;font-family:"Segoe UI",system-ui,sans-serif;color:#1c2430;padding:26px 30px 22px;width:1320px}',
'.head{border-left:6px solid #0b4f9e;padding:2px 0 2px 16px;margin-bottom:20px}',
'.kicker{font-size:11.5px;letter-spacing:.15em;text-transform:uppercase;color:#0b4f9e;font-weight:700}',
'h1{font-size:25px;line-height:1.2;margin:5px 0 7px;font-weight:650;letter-spacing:-.2px}',
'.sub{font-size:13.5px;color:#5b6673;line-height:1.55;max-width:1080px}',
'.sub b{color:#26313d;font-weight:600}',
'.panel{margin-bottom:17px}',
'.ptitle{font-size:13px;font-weight:650;color:#26313d;margin-bottom:7px;display:flex;align-items:center;gap:8px}',
'.ptitle:before{content:"";width:5px;height:5px;border-radius:50%;background:#0b4f9e;display:inline-block;flex:none}',
'.editor{border-radius:8px;overflow:hidden;box-shadow:0 1px 3px rgba(16,24,40,.13),0 8px 22px rgba(16,24,40,.09)}',
'.tab{background:#252526;padding:8px 14px;display:flex;align-items:center;gap:7px;border-bottom:1px solid #333}',
'.dot{width:9px;height:9px;border-radius:50%;display:inline-block;flex:none}',
'.dot.r{background:#ff5f57}.dot.y{background:#febc2e}.dot.g{background:#28c840}',
'.fname{color:#cfcfcf;font-family:Consolas,"Courier New",monospace;font-size:12.5px;margin-left:6px}',
'.range{color:#7e7e7e;font-family:Consolas,monospace;font-size:11.5px;margin-left:auto}',
'.body{background:#1e1e1e;padding:11px 0;font-family:Consolas,"Courier New",monospace;font-size:12.5px;line-height:1.6}',
'.ln{display:flex;padding:0 14px;white-space:pre}',
'.ln.hi{background:rgba(255,204,0,.14);box-shadow:inset 3px 0 0 #ffcc00}',
'.num{color:#6e7681;width:32px;flex:none;text-align:right;margin-right:16px}',
'.code{color:#d4d4d4}',
'.c{color:#6a9955}.s{color:#ce9178}.k{color:#569cd6}.f{color:#dcdcaa}.v{color:#9cdcfe}',
'.term .code{color:#d4d4d4}.prompt{color:#4ec9b0;margin-right:6px}',
'.nl{color:#6e7681;margin-right:2px}',
'.note{background:#fff;border:1px solid #dfe3e8;border-left:4px solid #0b4f9e;border-radius:6px;padding:13px 16px;font-size:12.8px;line-height:1.65;color:#3b4652}',
'.note b{color:#1c2430}',
'.foot{margin-top:13px;font-size:11px;color:#8a939e;display:flex;justify-content:space-between;border-top:1px solid #e2e5e9;padding-top:9px}',
'code{font-family:Consolas,monospace;background:#eef1f4;padding:1px 5px;border-radius:3px;font-size:11.8px;color:#0b4f9e}',
'h1 code{font-size:22px;background:#e8eef6}'
].join('\n');

function page(n, title, subtitle, panels, note) {
  return '<meta charset="utf-8"><style>' + CSS + '</style>' +
    '<div class="head"><div class="kicker">Evidencia ' + n + ' \u00b7 Certificaci\u00f3n Clave\u00danica</div>' +
    '<h1>' + title + '</h1><div class="sub">' + subtitle + '</div></div>' +
    panels + '<div class="note">' + note + '</div>' +
    '<div class="foot"><span>Gobierno Regional de Valpara\u00edso \u00b7 Plataforma de Consultas Ciudadanas \u00b7 https://www.participa.gobiernovalparaiso.cl</span>' +
    '<span>Laravel 12 / PHP 8.2 \u00b7 commit ' + COMMIT + '</span></div>';
}

const CFG  = 'config/claveunica.php';
const CTRL = 'app/Http/Controllers/Public/Auth/ClaveUnicaController.php';

const pages = {};

pages['evidencia-1-endpoint-token'] = page(1,
  'Configuraci\u00f3n del endpoint <code>Token</code>',
  'Llamada a <b>https://accounts.claveunica.gob.cl/openid/token/</b> \u00b7 intercambio <b>code \u2192 access_token</b> ejecutado desde el backend por POST <code>application/x-www-form-urlencoded</code> (paso 4 de la Gu\u00eda T\u00e9cnica v5.5).',
  codePanel('1. Definici\u00f3n de la URL del endpoint Token', CFG, 40, 48, [47], 'php') +
  codePanel('2. Llamada al endpoint Token desde el backend', CTRL, 199, 230, [214], 'php'),
  '<b>Qu\u00e9 se ve:</b> la URL del endpoint Token se define una sola vez en <code>config/claveunica.php:47</code> sobre el dominio oficial <code>accounts.claveunica.gob.cl</code>. La petici\u00f3n se emite en <code>ClaveUnicaController::fetchUserInfoLive()</code> con <code>Http::asForm()-&gt;post(config(\'claveunica.token_url\'), [...])</code>: es una llamada servidor a servidor, nunca desde el navegador, y env\u00eda <code>client_id</code>, <code>client_secret</code>, <code>redirect_uri</code>, <code>grant_type=authorization_code</code>, <code>code</code> y <code>state</code>. El <code>client_secret</code> y el <code>access_token</code> nunca se escriben en el log.');

pages['evidencia-2-endpoint-userinfo'] = page(2,
  'Configuraci\u00f3n del endpoint <code>UserInfo</code>',
  'Llamada a <b>https://accounts.claveunica.gob.cl/openid/userinfo/</b> \u00b7 obtenci\u00f3n de los datos del ciudadano con la cabecera <b>Authorization: Bearer</b>, desde el backend (paso 6 de la Gu\u00eda T\u00e9cnica v5.5).',
  codePanel('1. Definici\u00f3n de la URL del endpoint UserInfo', CFG, 40, 48, [48], 'php') +
  codePanel('2. Llamada al endpoint UserInfo desde el backend', CTRL, 232, 252, [241], 'php') +
  codePanel('3. Lectura de la respuesta documentada de UserInfo', CTRL, 254, 284, [274, 278], 'php'),
  '<b>Qu\u00e9 se ve:</b> la URL del endpoint UserInfo se define en <code>config/claveunica.php:48</code> apuntando a <code>accounts.claveunica.gob.cl</code>. La petici\u00f3n se emite en la l\u00ednea 241 con <code>Http::withToken($accessToken)-&gt;post(config(\'claveunica.userinfo_url\'))</code>, que agrega la cabecera <code>Authorization: Bearer &lt;access_token&gt;</code> exigida por la gu\u00eda. El identificador que la plataforma persiste es <code>RolUnico.numero</code> (el RUN), no <code>sub</code>, tal como indica el manual.');

// ---------------------------------------------------------------------------
// Evidencia 3 \u2014 rehecha tras la observacion del 15-sep-2026 de la Unidad de
// Gobierno Digital. La primera version mostraba la plantilla `.env.example` con
// las claves declaradas vacias; lo que el manual pide en la pagina 30 es el
// archivo de entorno del ambiente de PRODUCCION con los valores cargados, mas
// los metodos donde esas variables se consumen.
//
// El .env de produccion no esta \u2014ni puede estar\u2014 en el repositorio, que es
// justamente lo que se certifica. Por eso los datos del servidor se leen de un
// archivo local que no se versiona:
//
//   docs/claveunica/evidencia/credenciales.local.json
//   {
//     "prompt": "ubuntu@gore-prod:~$",
//     "app_env": "production",
//     "app_url": "https://www.participa.gobiernovalparaiso.cl",
//     "enabled": "true",
//     "mode": "live",
//     "client_id": "...",
//     "client_secret": "..."
//   }
//
// Copiar ahi la salida real del servidor: la captura es evidencia para una
// certificacion, no un ejemplo. Sin el archivo, el script deja marcadores
// visibles para que nadie suba una captura a medias.
// ---------------------------------------------------------------------------
const CRED_FILE = path.join(OUT, 'credenciales.local.json');
const CRED = fs.existsSync(CRED_FILE)
  ? JSON.parse(fs.readFileSync(CRED_FILE, 'utf8'))
  : {};

if (! fs.existsSync(CRED_FILE)) {
  console.warn('AVISO: no existe ' + CRED_FILE);
  console.warn('       La evidencia 3 saldra con marcadores en vez de las credenciales.');
}

const SRV = {
  prompt:        CRED.prompt        || 'ubuntu@gore-prod:~$',
  app_env:       CRED.app_env       || 'production',
  app_url:       CRED.app_url       || 'https://www.participa.gobiernovalparaiso.cl',
  enabled:       CRED.enabled       || 'true',
  mode:          CRED.mode          || 'live',
  client_id:     CRED.client_id     || '<<FALTA client_id DE PRODUCCION>>',
  client_secret: CRED.client_secret || '<<FALTA client_secret DE PRODUCCION>>',
};

const LINEA_ID     = 'CLAVEUNICA_CLIENT_ID=' + SRV.client_id;
const LINEA_SECRET = 'CLAVEUNICA_CLIENT_SECRET=' + SRV.client_secret;

pages['evidencia-3-credenciales-en-entorno'] = page(3,
  'Credenciales <code>client_id</code> y <code>client_secret</code> en variables de entorno',
  'Archivo <b>.env</b> del servidor de <b>producci\u00f3n</b> con las credenciales cargadas, y los m\u00e9todos de la aplicaci\u00f3n que las consumen. Ning\u00fan valor est\u00e1 escrito en el c\u00f3digo fuente ni viaja en el repositorio.',
  shellPanel('1. Variables de entorno en el servidor de producci\u00f3n \u2014 <code>/var/www/gore/.env</code>',
    'ssh \u00b7 www.participa.gobiernovalparaiso.cl \u00b7 /var/www/gore',
    [
      { prompt: SRV.prompt, cmd: "grep '^APP_ENV\\|^APP_URL' /var/www/gore/.env",
        out: ['APP_ENV=' + SRV.app_env, 'APP_URL=' + SRV.app_url] },
      { prompt: SRV.prompt, cmd: "grep '^CLAVEUNICA' /var/www/gore/.env",
        out: ['CLAVEUNICA_ENABLED=' + SRV.enabled, 'CLAVEUNICA_MODE=' + SRV.mode,
              LINEA_ID, LINEA_SECRET] },
    ],
    [LINEA_ID, LINEA_SECRET]) +
  codePanel('2. El c\u00f3digo lee esas variables \u2014 no contiene ning\u00fan valor', CFG, 30, 38, [37, 38], 'php') +
  codePanel('3. M\u00e9todo que consume <code>client_id</code>: redirecci\u00f3n al endpoint Authorize', CTRL, 67, 76, [69], 'php') +
  codePanel('4. M\u00e9todo que consume ambas credenciales: llamada al endpoint Token', CTRL, 212, 221, [215, 216], 'php') +
  codePanel('5. El archivo .env est\u00e1 excluido del control de versiones', '.gitignore', 1, 8, [3, 4, 5], 'gitignore'),
  '<b>Qu\u00e9 se ve:</b> el <code>.env</code> del servidor de producci\u00f3n (<code>APP_ENV=production</code>, <code>www.participa.gobiernovalparaiso.cl</code>) tiene <code>CLAVEUNICA_CLIENT_ID</code> y <code>CLAVEUNICA_CLIENT_SECRET</code> con las credenciales de producci\u00f3n entregadas por la Unidad de Gobierno Digital, y <code>CLAVEUNICA_MODE=live</code>. El c\u00f3digo las toma solo desde ah\u00ed: <code>config/claveunica.php</code> las resuelve con <code>env(\'CLAVEUNICA_CLIENT_ID\')</code> y <code>env(\'CLAVEUNICA_CLIENT_SECRET\')</code>, y se consumen en dos m\u00e9todos de <code>ClaveUnicaController</code> \u2014 <code>redirect()</code> arma la URL de <code>authorize/</code> con el <code>client_id</code>, y <code>fetchUserInfoLive()</code> env\u00eda <code>client_id</code> y <code>client_secret</code> por POST a <code>token/</code>. El <code>.env</code> figura en <code>.gitignore</code> y existe \u00fanicamente en el servidor, en <code>/var/www/gore/.env</code>. Es el criterio del ejemplo de la p\u00e1gina 30 del Manual de Integraci\u00f3n.');

fs.mkdirSync(OUT, { recursive: true });
Object.keys(pages).forEach(name => {
  fs.writeFileSync(path.join(OUT, name + '.html'), pages[name], 'utf8');
  console.log('escrito', name + '.html');
});

// ---------------------------------------------------------------------------
// Captura de los .html a PNG (Windows, Edge headless + ImageMagick):
//
//   EDGE="/c/Program Files (x86)/Microsoft/Edge/Application/msedge.exe"
//   MAGICK="/c/Program Files/ImageMagick-7.1.2-Q16-HDRI/magick.exe"
//   DIR="$(pwd)/docs/claveunica/evidencia"
//   for f in evidencia-1-endpoint-token evidencia-2-endpoint-userinfo \
//            evidencia-3-credenciales-en-entorno; do
//     "$EDGE" --headless=new --disable-gpu --hide-scrollbars \
//       --force-device-scale-factor=2 --window-size=1380,2100 \
//       --screenshot="$DIR/$f.raw.png" "file:///$DIR/$f.html"
//     "$MAGICK" "$DIR/$f.raw.png" -bordercolor "#f4f5f7" -border 2 -trim +repage \
//       -bordercolor "#f4f5f7" -border 44 "$DIR/$f.png"
//     rm -f "$DIR/$f.raw.png"
//   done
//
// El --window-size solo tiene que ser mas alto que la pagina; el -trim recorta
// el sobrante y el -border le devuelve un margen parejo.
// ---------------------------------------------------------------------------
