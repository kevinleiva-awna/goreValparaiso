<?php

/**
 * Carga los datos de la capacitacion de usuarios del backoffice: tres
 * consultas ficticias, una en cada estado que ve el ciudadano.
 *
 *  A. Zonificacion del Borde Costero (ZUBC), ACTIVA. Es la consulta de trabajo:
 *     - los tres tipos de participante, pasaporte y ClaveUnica;
 *     - envios con varias observaciones y con adjuntos (para el ZIP);
 *     - respuestas publicadas (se ven en la ficha publica) y borradores;
 *     - cuatro observaciones casi iguales sobre "acceso a la playa", sin
 *       responder, para hacer la respuesta en lote en vivo;
 *     - un envio de prueba ("prueba prueba 123") para archivarlo en vivo;
 *     - un antecedente reemplazado, para mostrar el versionado.
 *  C. Modificacion del PRI Valle del Aconcagua (IPT), CERRADA, con todas sus
 *     observaciones respondidas: muestra un expediente terminado.
 *  D. PROT Imagen Objetivo, PUBLICADA y proxima: se anuncia pero aun no recibe
 *     observaciones.
 *
 * (La consulta B no esta aqui: se crea en vivo durante la sesion.)
 *
 * Todo se ejecuta como una sola linea de tiempo con un reloj simulado
 * (22-jun a 02-oct-2026): el panel muestra actividad repartida en varios dias
 * y la bitacora, que ordena por id, queda en orden cronologico. Todas las
 * personas, empresas y organizaciones son ficticias; los correos de los
 * participantes usan example.com.
 *
 * Uso (en el servidor, como el usuario dueno de la app):
 *   php demo-capacitacion.php [--root=/var/www/gore] [--autor=correo] [--archivos=dir] [--dry-run]
 *
 *   --autor     funcionario o super-admin al que se atribuyen las consultas,
 *               los antecedentes y las respuestas. Por defecto, el primer
 *               super-admin activo.
 *   --archivos  ademas, deja en ese directorio los archivos para la parte en
 *               vivo: el antecedente de la consulta B (y su version 2 para
 *               mostrar el reemplazo) y una foto para adjuntar al participar.
 *   --dry-run   hace todo dentro de una transaccion y la revierte al final (y
 *               borra los archivos que alcanzo a subir).
 *
 * No corre en produccion. Las consultas que ya existen (por slug) se omiten,
 * asi que se puede volver a correr sin duplicar nada.
 */

use App\Models\Consultation;
use App\Models\ConsultationDocument;
use App\Models\InstitutionalResponse;
use App\Models\Observation;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Activitylog\CauserResolver;

$opts = getopt('', ['root:', 'autor:', 'archivos:', 'dry-run']);
$root = $opts['root'] ?? (is_file(__DIR__.'/../artisan') ? dirname(__DIR__) : '/var/www/gore');
$dryRun = array_key_exists('dry-run', $opts);

require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

if (app()->environment('production')) {
    fwrite(STDERR, "ERROR: este script carga datos ficticios y no corre en produccion.\n");
    exit(1);
}

$autor = isset($opts['autor'])
    ? User::where('email', $opts['autor'])->where('is_active', true)->first()
    : User::where('role', User::ROLE_SUPER_ADMIN)->where('is_active', true)->orderBy('id')->first();
if (! $autor || ! $autor->isStaff()) {
    fwrite(STDERR, "ERROR: no hay un funcionario o super-admin activo al que atribuir las consultas y las respuestas.\n");
    exit(1);
}

// ---------------------------------------------------------------------------
// Utilidades
// ---------------------------------------------------------------------------

/** RUT con digito verificador modulo 11, en el formato canonico "12345678-9". */
function rut(int $body): string
{
    $sum = 0;
    $factor = 2;
    for ($n = $body; $n > 0; $n = intdiv($n, 10)) {
        $sum += ($n % 10) * $factor;
        $factor = $factor === 7 ? 2 : $factor + 1;
    }
    $dv = 11 - ($sum % 11);

    return $body.'-'.match ($dv) { 11 => '0', 10 => 'K', default => (string) $dv };
}

/**
 * PDF de una pagina escrito a mano (Helvetica, WinAnsi), sin dependencias.
 * $context es la linea bajo el titulo; $drawing, operadores PDF extra.
 */
function pdf(string $title, string $context, array $paragraphs, string $drawing = ''): string
{
    $cp = fn (string $s) => iconv('UTF-8', 'Windows-1252//TRANSLIT', $s);
    $esc = fn (string $s) => strtr($s, ['\\' => '\\\\', '(' => '\\(', ')' => '\\)']);

    $text = 'BT /F1 11 Tf 0.15 0.15 0.15 rg 15 TL 56 710 Td';
    foreach ($paragraphs as $p) {
        foreach (explode("\n", wordwrap($cp($p), 90, "\n", true)) as $line) {
            $text .= ' ('.$esc($line).') Tj T*';
        }
        $text .= ' T*';
    }
    $text .= ' ET';

    $stream = implode("\n", [
        '0.12 0.30 0.55 rg 0 802 595 40 re f',
        'BT /F2 9 Tf 1 1 1 rg 56 817 Td ('.$esc($cp('DOCUMENTO DE PRUEBA — contenido ficticio para la capacitación de usuarios')).') Tj ET',
        'BT /F2 17 Tf 0.1 0.1 0.1 rg 56 760 Td ('.$esc($cp($title)).') Tj ET',
        'BT /F1 9 Tf 0.4 0.4 0.4 rg 56 742 Td ('.$esc($cp($context.' · Gobierno Regional de Valparaíso')).') Tj ET',
        $drawing,
        $text,
    ]);

    $objects = [
        '<< /Type /Catalog /Pages 2 0 R >>',
        '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
        '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R /F2 5 0 R >> >> /Contents 6 0 R >>',
        '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
        '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
        '<< /Length '.strlen($stream)." >>\nstream\n".$stream."\nendstream",
    ];

    $out = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
    $offsets = [];
    foreach ($objects as $i => $obj) {
        $offsets[] = strlen($out);
        $out .= ($i + 1)." 0 obj\n".$obj."\nendobj\n";
    }
    $xref = strlen($out);
    $out .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
    foreach ($offsets as $offset) {
        $out .= sprintf("%010d 00000 n \n", $offset);
    }

    return $out."trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";
}

/** Plano esquematico: franja de mar a la izquierda y zonas con su leyenda. */
function mapDrawing(array $zones, string $water): string
{
    $cp = fn (string $s) => iconv('UTF-8', 'Windows-1252//TRANSLIT', $s);
    $ops = [
        '0.80 0.90 0.98 rg 56 230 240 350 re f',
        '0.30 0.45 0.65 RG 2 w 296 230 m 290 300 l 300 380 l 292 460 l 300 580 l S',
        'BT /F2 10 Tf 0.25 0.40 0.60 rg 110 400 Td ('.$cp($water).') Tj ET',
    ];
    $y = 580;
    foreach ($zones as [$rgb, $h, $label]) {
        $y -= $h;
        $ops[] = "{$rgb} rg 300 {$y} 150 {$h} re f";
        $ops[] = '0.2 0.2 0.2 rg BT /F1 9 Tf 460 '.($y + intdiv($h, 2) - 3).' Td ('.$cp($label).') Tj ET';
    }

    return implode("\n", $ops);
}

/** Imagen tipo fotografia de playa (JPEG) o croquis de un poligono (PNG). */
function picture(string $kind, string $caption): string
{
    $im = imagecreatetruecolor(1024, 683);
    $c = fn (int $r, int $g, int $b) => imagecolorallocate($im, $r, $g, $b);

    if ($kind === 'photo') {
        for ($y = 0; $y < 300; $y++) {
            imageline($im, 0, $y, 1023, $y, $c(120 + intdiv($y, 4), 170 + intdiv($y, 6), 225));
        }
        imagefilledrectangle($im, 0, 300, 1023, 470, $c(38, 98, 140));
        for ($i = 0; $i < 40; $i++) {
            $y = 310 + $i * 4;
            imageline($im, mt_rand(0, 900), $y, mt_rand(100, 1023), $y, $c(230, 240, 245));
        }
        imagefilledrectangle($im, 0, 470, 1023, 682, $c(214, 194, 150));
        imagefilledellipse($im, 820, 120, 90, 90, $c(250, 235, 180));
        imagefilledpolygon($im, [0, 300, 180, 210, 360, 300], $c(70, 90, 70));
    } else {
        imagefilledrectangle($im, 0, 0, 1023, 682, $c(240, 232, 210));
        imagefilledrectangle($im, 0, 0, 330, 682, $c(170, 205, 235));
        imagesetthickness($im, 3);
        imageline($im, 330, 0, 300, 682, $c(60, 90, 130));
        imagefilledpolygon($im, [360, 120, 620, 90, 700, 300, 520, 420, 380, 330], $c(150, 205, 140));
        imagesetthickness($im, 4);
        imagepolygon($im, [360, 120, 620, 90, 700, 300, 520, 420, 380, 330], $c(40, 110, 50));
        imagestring($im, 5, 420, 240, 'Zona de proteccion propuesta', $c(30, 70, 35));
    }

    imagefilledrectangle($im, 0, 640, 1023, 682, $c(20, 20, 20));
    imagestring($im, 4, 16, 652, $caption.'  (imagen de prueba)', $c(255, 255, 255));

    ob_start();
    $kind === 'photo' ? imagejpeg($im, null, 82) : imagepng($im);
    imagedestroy($im);

    return (string) ob_get_clean();
}

// ---------------------------------------------------------------------------
// Datos de la demo
// ---------------------------------------------------------------------------

$ua = [
    'android' => 'Mozilla/5.0 (Linux; Android 14; SM-A546E) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Mobile Safari/537.36',
    'iphone' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.6 Mobile/15E148 Safari/604.1',
    'windows' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36',
    'mac' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.6 Safari/605.1.15',
];

$firma = "\n\nDivisión de Planificación y Desarrollo\nGobierno Regional de Valparaíso";

$ctxA = 'Zonificación del Borde Costero — Litoral Central';
$ctxC = 'Modificación al PRI — Valle del Aconcagua';
$ctxD = 'Plan Regional de Ordenamiento Territorial — Imagen Objetivo';

$consultas = [];

// --- A. Activa: la consulta de trabajo de la sesion ------------------------

$consultas[] = [
    'slug' => 'zonificacion-borde-costero-litoral-central',
    'title' => $ctxA,
    'summary' => 'Propuesta de usos preferentes del borde costero entre Algarrobo y Santo Domingo. Revisa los antecedentes y envía tus observaciones.',
    'description' => "La Zonificación del Uso del Borde Costero (ZUBC) define qué usos son preferentes en cada tramo de la costa: conservación, pesca artesanal, turismo, actividad portuaria o residencial. Esta propuesta abarca el litoral central de la región, entre las comunas de Algarrobo y Santo Domingo, y se elaboró a partir de talleres con municipios, organizaciones de pescadores y servicios públicos.\n\n"
        ."Durante el periodo de consulta pública, cualquier persona, empresa u organización puede revisar los antecedentes técnicos y enviar observaciones formales. Todas las observaciones quedan registradas en el expediente del proceso y serán respondidas por el Gobierno Regional.\n\n"
        .'Antes de observar, te recomendamos revisar el resumen ejecutivo y la cartografía de la propuesta.',
    'type' => Consultation::TYPE_ZUBC,
    'starts' => '2026-09-21 09:00',
    'ends' => '2026-10-30 23:59',
    'auth' => [Consultation::AUTH_CLAVEUNICA, Consultation::AUTH_GUEST],
    'creada' => '2026-09-15 10:20',
    'estados' => [['2026-09-21 08:55', Consultation::STATUS_ACTIVE]],
    'docs' => [
        ['at' => '2026-09-18 15:40', 'title' => 'Memoria explicativa',
            'desc' => 'Fundamentos, diagnóstico y objetivos de la propuesta de zonificación.',
            'file' => 'memoria-explicativa-zubc-litoral-central.pdf',
            'make' => fn () => pdf('Memoria explicativa', $ctxA, [
                'La presente memoria describe los fundamentos de la propuesta de Zonificación del Uso del Borde Costero para el litoral central de la región, entre las comunas de Algarrobo y Santo Domingo.',
                'Diagnóstico. El borde costero concentra usos que compiten entre sí: pesca artesanal, turismo, residencia de temporada, actividad portuaria y conservación de humedales. La falta de una zonificación vigente ha generado conflictos de acceso a las playas y presión inmobiliaria sobre zonas de riesgo.',
                'Objetivos. (1) Definir usos preferentes para cada tramo de costa. (2) Resguardar los humedales y sitios de nidificación. (3) Reconocer las caletas y su infraestructura. (4) Garantizar el acceso público a las playas. (5) Restringir la edificación en zonas expuestas a tsunami y marejadas.',
                'Metodología. La propuesta se construyó con información de los municipios, de las organizaciones de pescadores y de los servicios públicos con competencia en el borde costero, y se somete ahora a consulta pública.',
            ])],
        ['at' => '2026-09-18 15:46', 'title' => 'Propuesta de zonificación (cartografía)',
            'desc' => 'Plano con las zonas de uso preferente propuestas para el borde costero.',
            'file' => 'cartografia-propuesta-zonificacion.pdf',
            'make' => fn () => pdf('Propuesta de zonificación — cartografía', $ctxA, [
                'Plano esquemático de las zonas de uso preferente. La cartografía de detalle, a escala 1:10.000, se publicará con la propuesta definitiva.',
            ], mapDrawing([
                ['0.55 0.78 0.55', 60, 'Conservación'],
                ['0.98 0.80 0.45', 80, 'Turismo'],
                ['0.60 0.75 0.95', 50, 'Pesca artesanal'],
                ['0.95 0.65 0.60', 90, 'Residencial'],
                ['0.75 0.75 0.75', 70, 'Portuaria'],
            ], 'OCEANO PACIFICO'))],
        ['at' => '2026-09-18 15:52', 'title' => 'Resumen ejecutivo',
            'desc' => 'Síntesis de la propuesta en lenguaje ciudadano.',
            'file' => 'resumen-ejecutivo-zubc.pdf',
            'make' => fn () => pdf('Resumen ejecutivo', $ctxA, [
                '¿Qué es la zonificación del borde costero? Es el instrumento que define para qué se puede usar cada tramo de la costa.',
                '¿Qué propone? Cinco tipos de zona: conservación, turismo, pesca artesanal, residencial y portuaria.',
            ]),
            // Reemplazo versionado, igual que ConsultationDocumentController::replace().
            'v2' => ['at' => '2026-09-24 09:30', 'make' => fn () => pdf('Resumen ejecutivo (versión 2)', $ctxA, [
                '¿Qué es la zonificación del borde costero? Es el instrumento que define para qué se puede usar cada tramo de la costa, entre Algarrobo y Santo Domingo.',
                '¿Qué propone? Cinco tipos de zona: conservación, turismo, pesca artesanal, residencial y portuaria. Además, asegura accesos públicos a las playas y restringe la edificación en zonas de riesgo.',
                '¿Cómo participar? Hasta el 30 de octubre puedes enviar observaciones en participa.gobiernovalparaiso.cl, con ClaveÚnica o sin registro.',
                'Versión 2: corrige la fecha de cierre de la consulta indicada en la versión anterior.',
            ])]],
    ],
    'envios' => [
        [
            'at' => '2026-09-22 19:42', 'ip' => '203.0.113.24', 'ua' => 'android',
            'quien' => ['tipo' => 'natural', 'nombre' => 'Carolina Muñoz Pizarro', 'rut' => 16234871,
                'email' => 'carolina.munoz@example.com', 'telefono' => '+56 9 6123 4471', 'comuna' => 'El Quisco', 'edad' => 34],
            'obs' => [
                ['tema' => 'Uso de suelo', 'asunto' => 'Zona residencial junto al estero',
                    'texto' => 'En la cartografía propuesta, el tramo entre la desembocadura del estero y la playa grande de El Quisco aparece como zona de uso preferente residencial. Ese sector se inunda todos los inviernos y es parte del humedal costero. Solicito que se reclasifique como zona de conservación o, al menos, que se establezca una franja de protección de 100 metros desde el borde del humedal.',
                    'adjunto' => ['humedal-el-quisco.jpg', fn () => picture('photo', 'Humedal y desembocadura del estero, invierno 2026')]],
                ['tema' => 'Areas verdes', 'asunto' => 'Plazas y miradores de la costanera',
                    'texto' => 'Pido que la propuesta incluya explícitamente los miradores y plazas existentes en la costanera como áreas verdes de uso público. Hoy varios de ellos no aparecen en el plano y temo que queden disponibles para otros usos.'],
            ],
        ],
        [
            'at' => '2026-09-23 08:15', 'ip' => '198.51.100.73', 'ua' => 'windows',
            'quien' => ['tipo' => 'claveunica', 'nombre' => 'Jorge Andrés', 'apellido' => 'Valdivia Rojas', 'rut' => 10987654,
                'email' => 'jorge.valdivia@example.com'],
            'obs' => [
                ['ref' => 'tsunami', 'tema' => 'Riesgo natural', 'asunto' => 'Vías de evacuación ante tsunami',
                    'texto' => 'La propuesta no muestra las vías de evacuación ante tsunami ni las zonas de seguridad. En temporada alta la población de las comunas del litoral se multiplica y varias calles de evacuación terminan en sectores que la propuesta define como de uso turístico intensivo. Solicito incorporar la cartografía de riesgo y restringir nuevas construcciones bajo la cota de inundación.'],
            ],
        ],
        [
            'at' => '2026-09-24 11:30', 'ip' => '192.0.2.140', 'ua' => 'windows',
            'quien' => ['tipo' => 'pj', 'razon' => 'Cooperativa de Pescadores Artesanales Caleta Sur Ltda.', 'fantasia' => 'Caleta Sur',
                'rut' => 76543219, 'email' => 'directiva@caletasur.example.com', 'telefono' => '+56 35 221 4380', 'direccion' => 'Av. Costanera 1450, San Antonio'],
            'obs' => [
                ['ref' => 'caleta', 'tema' => 'Equipamiento', 'asunto' => 'Infraestructura de la caleta',
                    'texto' => 'La cooperativa reúne a 48 pescadores artesanales que operan desde la caleta. Solicitamos que la zona de uso preferente de pesca artesanal incluya el área de varado de embarcaciones, el galpón de redes y el acceso vehicular para el retiro de la pesca, que en el plano actual quedan dentro de la zona turística.',
                    'adjunto' => ['carta-cooperativa-caleta-sur.pdf', fn () => pdf('Carta de la Cooperativa Caleta Sur', $ctxA, [
                        'Señores División de Planificación y Desarrollo, Gobierno Regional de Valparaíso:',
                        'Por medio de la presente, la directiva de la Cooperativa de Pescadores Artesanales Caleta Sur Ltda. hace llegar sus observaciones a la propuesta de Zonificación del Borde Costero del Litoral Central.',
                        'Nuestra organización reúne a 48 socios que dependen de la caleta para su sustento. El área de varado, el galpón de redes y el acceso vehicular son indispensables para la faena diaria y hoy quedan dentro de la zona turística propuesta.',
                        'Solicitamos que esas instalaciones se incorporen a la zona de uso preferente de pesca artesanal y que en la zona mixta se reconozca la preferencia de la actividad pesquera en horario de faena.',
                        'Saluda atentamente, Directiva Cooperativa Caleta Sur.',
                    ])]],
                ['tema' => 'Uso de suelo', 'asunto' => 'Compatibilidad entre pesca y turismo',
                    'texto' => 'No nos oponemos al turismo, pero pedimos que la ordenanza deje claro que en la zona mixta la actividad pesquera tiene preferencia en horario de faena (05:00 a 11:00), para evitar conflictos con kayaks y embarcaciones recreativas.'],
            ],
        ],
        [
            'at' => '2026-09-25 21:05', 'ip' => '203.0.113.88', 'ua' => 'iphone',
            'quien' => ['tipo' => 'natural', 'nombre' => 'Lucía Fernández Gómez', 'pasaporte' => 'AAB482913',
                'email' => 'lucia.fernandez@example.com', 'comuna' => 'Algarrobo', 'edad' => 41],
            'obs' => [
                ['ref' => 'ciclovia', 'tema' => 'Vialidad', 'asunto' => 'Ciclovía costera continua',
                    'texto' => 'Vivo en Algarrobo hace seis años y me muevo en bicicleta. La propuesta menciona un corredor costero, pero no indica si tendrá ciclovía. Sugiero que se considere una ciclovía continua entre Algarrobo y El Tabo, segregada del tránsito vehicular, que conecte las playas y los servicios.'],
            ],
        ],
        [
            'at' => '2026-09-26 18:20', 'ip' => '198.51.100.12', 'ua' => 'mac',
            'quien' => ['tipo' => 'org', 'razon' => 'Agrupación Amigos del Humedal de Cartagena', 'rut' => 65123987,
                'email' => 'amigosdelhumedal@example.com', 'telefono' => '+56 9 7745 2210', 'direccion' => 'Pasaje Los Aromos 33, Cartagena'],
            'obs' => [
                ['ref' => 'humedal', 'tema' => 'Areas verdes', 'asunto' => 'Protección del humedal',
                    'texto' => 'El humedal es sitio de nidificación de aves migratorias, entre ellas el rayador y el pilpilén. Proponemos ampliar la zona de conservación hacia el norte, según el polígono que adjuntamos, e impedir el tránsito de vehículos motorizados por la playa en ese tramo.',
                    'adjunto' => ['propuesta-zona-proteccion-humedal.png', fn () => picture('map', 'Propuesta de la agrupacion: ampliacion de la zona de conservacion')]],
                ['tema' => 'Patrimonio', 'asunto' => 'Casonas de veraneo del sector alto',
                    'texto' => 'En el sector alto existen casonas de veraneo de principios del siglo XX que forman parte de la identidad del balneario. Solicitamos que la zonificación reconozca ese conjunto como zona de interés patrimonial y que se coordine con la municipalidad su protección en el plan regulador.'],
                ['tema' => 'Riesgo natural', 'asunto' => 'Marejadas y retroceso de la costa',
                    'texto' => 'Las marejadas de los últimos inviernos destruyeron parte de la costanera. Pedimos que la propuesta considere el retroceso de la línea de costa por cambio climático y que no se autoricen nuevas edificaciones en la franja más expuesta.'],
            ],
        ],
        [
            'at' => '2026-09-27 10:48', 'ip' => '203.0.113.150', 'ua' => 'android',
            'quien' => ['tipo' => 'natural', 'nombre' => 'Pedro Soto Araya', 'rut' => 13456092,
                'email' => 'pedro.soto@example.com', 'telefono' => '+56 9 5310 9921', 'comuna' => 'El Tabo', 'edad' => 58],
            'obs' => [
                ['tema' => 'Vialidad', 'asunto' => 'Acceso a la playa bloqueado',
                    'texto' => 'Desde hace dos años el acceso a la playa del sector norte de El Tabo está cerrado por un condominio. Los vecinos tenemos que caminar casi un kilómetro por la carretera para llegar. Pido que la zonificación garantice un acceso a la playa público cada cierta distancia.'],
            ],
        ],
        [
            'at' => '2026-09-28 13:02', 'ip' => '192.0.2.61', 'ua' => 'iphone',
            'quien' => ['tipo' => 'claveunica', 'nombre' => 'Marcela', 'apellido' => 'Contreras Vega', 'rut' => 16789012,
                'email' => 'marcela.contreras@example.com'],
            'obs' => [
                ['tema' => 'Vialidad', 'asunto' => 'Accesos públicos al mar',
                    'texto' => 'Solicito que la propuesta asegure el acceso a la playa libre y gratuito para todas las personas. En varios tramos del litoral los únicos accesos pasan por terrenos privados y en verano se cobran estacionamientos para llegar al mar.'],
            ],
        ],
        [
            'at' => '2026-09-29 09:37', 'ip' => '198.51.100.201', 'ua' => 'windows',
            'quien' => ['tipo' => 'natural', 'nombre' => 'Rodrigo Pérez Lagos', 'rut' => 17654320,
                'email' => 'rodrigo.perez@example.com', 'comuna' => 'San Antonio'],
            'obs' => [
                ['tema' => 'Equipamiento', 'asunto' => 'Límite con la zona portuaria',
                    'texto' => 'El límite entre la zona portuaria y la zona de uso turístico en San Antonio no considera el aumento de tránsito de camiones previsto por la ampliación del puerto. Sugiero una zona de transición con equipamiento y áreas verdes que amortigüe el impacto en los barrios cercanos.'],
            ],
        ],
        [
            'at' => '2026-09-29 20:14', 'ip' => '203.0.113.9', 'ua' => 'android',
            'quien' => ['tipo' => 'natural', 'nombre' => 'Ignacio Herrera Díaz', 'rut' => 18345621,
                'email' => 'ignacio.herrera@example.com', 'comuna' => 'Algarrobo', 'edad' => 29],
            'obs' => [
                ['tema' => 'Uso de suelo', 'asunto' => null,
                    'texto' => 'Me preocupa que la zona residencial propuesta permita nuevos condominios frente al mar sin exigir un acceso a la playa público. Debería ser una condición obligatoria para cualquier proyecto en el borde costero.'],
            ],
        ],
        [
            'at' => '2026-09-30 15:26', 'ip' => '192.0.2.200', 'ua' => 'windows',
            'quien' => ['tipo' => 'pj', 'razon' => 'Inmobiliaria Altos del Pacífico SpA', 'fantasia' => 'Altos del Pacífico',
                'rut' => 76876543, 'email' => 'proyectos@altosdelpacifico.example.com', 'telefono' => '+56 2 2940 1180', 'direccion' => 'Av. Apoquindo 4500, oficina 1203, Las Condes'],
            'obs' => [
                ['ref' => 'inmobiliaria', 'tema' => 'Uso de suelo', 'asunto' => 'Altura máxima en la zona residencial sur',
                    'texto' => 'En representación de la empresa, solicitamos revisar la restricción de altura de dos pisos en la zona residencial del sector sur. Los terrenos cuentan con factibilidad sanitaria y la restricción impide desarrollar vivienda en una comuna con alta demanda. Adjuntamos un informe técnico con una propuesta de altura escalonada.',
                    'adjunto' => ['observacion-tecnica-altos-del-pacifico.pdf', fn () => pdf('Observación técnica — altura en zona residencial sur', $ctxA, [
                        'Inmobiliaria Altos del Pacífico SpA presenta la siguiente observación técnica a la propuesta de zonificación.',
                        '1. Antecedentes. Los predios del sector sur cuentan con factibilidad de agua potable y alcantarillado, y se ubican fuera de la cota de inundación por tsunami.',
                        '2. Problema. La restricción de dos pisos limita la oferta de vivienda en una comuna con alta demanda y empuja el crecimiento hacia sectores sin servicios.',
                        '3. Propuesta. Altura escalonada: dos pisos en la primera franja de 100 metros desde la línea de costa, cuatro pisos entre 100 y 300 metros, y seis pisos sobre los 300 metros.',
                        '4. Compromisos. Cada proyecto mantendría un acceso público a la playa y cesión de áreas verdes sobre el mínimo legal.',
                    ])]],
            ],
        ],
        [
            'at' => '2026-10-01 11:52', 'ip' => '198.51.100.45', 'ua' => 'mac',
            'quien' => ['tipo' => 'pj', 'razon' => 'Junta de Vecinos Villa Las Gaviotas', 'rut' => 65098761,
                'email' => 'jjvv.lasgaviotas@example.com', 'direccion' => 'Calle Las Gaviotas 120, El Tabo'],
            'obs' => [
                ['tema' => 'Vialidad', 'asunto' => 'Servidumbre de paso de la villa',
                    'texto' => 'La Junta de Vecinos solicita que se reconozca la servidumbre de paso que históricamente usaron los vecinos de la villa como acceso a la playa, y que se mantenga habilitada como acceso público en la nueva zonificación.'],
            ],
        ],
        [
            'at' => '2026-10-01 16:08', 'ip' => '203.0.113.177', 'ua' => 'iphone',
            'quien' => ['tipo' => 'natural', 'nombre' => 'Valentina Rojas Castillo', 'rut' => 21345678,
                'email' => 'valentina.rojas@example.com', 'comuna' => 'Santo Domingo', 'edad' => 22],
            'obs' => [
                ['ref' => 'presencial', 'tema' => 'Otro', 'asunto' => 'Más instancias presenciales',
                    'texto' => 'Muchas personas mayores de mi comuna no usan internet. Pido que se realicen jornadas presenciales para explicar la propuesta y recibir observaciones en papel, y que el resumen ejecutivo se entregue impreso en las municipalidades.'],
            ],
        ],
        [
            'at' => '2026-10-02 10:12', 'ip' => '192.0.2.33', 'ua' => 'android',
            'quien' => ['tipo' => 'natural', 'nombre' => 'Francisco Morales Núñez', 'rut' => 14567890,
                'email' => 'francisco.morales@example.com', 'telefono' => '+56 9 8802 3317', 'comuna' => 'Cartagena', 'edad' => 47],
            'obs' => [
                ['tema' => 'Patrimonio', 'asunto' => 'Balneario histórico de Cartagena',
                    'texto' => 'Cartagena fue uno de los primeros balnearios populares del país. Pido que la zonificación considere el paseo costero, sus escaleras y terrazas como parte del patrimonio del balneario y que cualquier intervención respete su carácter.',
                    'adjunto' => ['fotografia-paseo-costero.jpg', fn () => picture('photo', 'Paseo costero de Cartagena, septiembre 2026')]],
                ['tema' => 'Vialidad', 'asunto' => 'Estacionamientos en temporada alta',
                    'texto' => 'En verano los autos se estacionan sobre las veredas y la arena. Propongo que se definan zonas de estacionamiento fuera de la franja costera, con transporte de acercamiento a las playas.'],
            ],
        ],
        [
            // Envio de prueba: queda sin archivar para archivarlo en vivo (solo super-admin).
            'at' => '2026-10-02 16:47', 'ip' => '198.51.100.99', 'ua' => 'windows',
            'quien' => ['tipo' => 'natural', 'nombre' => 'test test', 'rut' => 11111111, 'email' => 'test@example.com'],
            'obs' => [
                ['tema' => null, 'asunto' => 'test', 'texto' => 'prueba prueba prueba 123 probando el formulario'],
            ],
        ],
    ],
    'respuestas' => [
        ['ref' => 'tsunami', 'at' => '2026-09-29 12:10', 'publicar' => true,
            'texto' => 'Agradecemos su observación. La cartografía de riesgo de tsunami será incorporada como antecedente de la propuesta definitiva, y las zonas bajo la cota de inundación quedarán con restricción a nuevas edificaciones de uso habitacional. Las vías de evacuación se mantendrán como condicionante en las zonas de uso turístico.'.$firma],
        ['ref' => 'ciclovia', 'at' => '2026-09-30 16:40', 'publicar' => true,
            'texto' => 'Agradecemos su aporte. La zonificación define usos preferentes del borde costero y no diseña infraestructura vial; sin embargo, su propuesta será remitida a los municipios de Algarrobo y El Tabo para su consideración en la planificación de ciclovías. La propuesta definitiva reconocerá el corredor costero como espacio compatible con la movilidad no motorizada.'.$firma],
        ['ref' => 'caleta', 'at' => '2026-10-01 10:05', 'publicar' => true,
            'texto' => 'Se acoge la observación. El área de varado, el galpón de redes y el acceso vehicular de la caleta serán incorporados a la zona de uso preferente de pesca artesanal en la cartografía definitiva.'.$firma],
        ['ref' => 'presencial', 'at' => '2026-10-01 17:30', 'publicar' => true,
            'texto' => 'Se acoge la observación. Durante el periodo de consulta se realizarán jornadas presenciales en las comunas del litoral, en coordinación con los municipios, donde se recibirán observaciones en papel que serán ingresadas al expediente. El resumen ejecutivo impreso estará disponible en las oficinas municipales.'.$firma],
        ['ref' => 'humedal', 'at' => '2026-10-02 11:00', 'publicar' => false,
            'texto' => 'Se acoge parcialmente la observación. El polígono propuesto por la agrupación será evaluado para ampliar la zona de conservación del humedal, y la restricción al tránsito de vehículos motorizados por la playa se incorporará como condición de uso en ese tramo.'.$firma],
        ['ref' => 'inmobiliaria', 'at' => '2026-10-02 12:20', 'publicar' => false,
            'texto' => 'No se acoge la observación. La restricción de altura en la zona residencial sur responde a la protección del paisaje costero y a la capacidad de la vialidad existente. La propuesta de altura escalonada podrá ser evaluada en la actualización del plan regulador comunal, que es el instrumento que fija las normas de edificación.'.$firma],
    ],
];

// --- C. Cerrada: un expediente terminado, todo respondido ------------------

$consultas[] = [
    'slug' => 'modificacion-pri-valle-aconcagua',
    'title' => 'Modificación al Plan Regulador Intercomunal — Valle del Aconcagua',
    'summary' => 'Ajuste de las zonas de extensión urbana y de las áreas de riesgo de inundación en las comunas del valle del Aconcagua.',
    'description' => "La modificación ajusta los límites de las zonas de extensión urbana del Plan Regulador Intercomunal y actualiza las áreas de riesgo de inundación a partir de los estudios hidráulicos más recientes del río Aconcagua.\n\n"
        .'La consulta pública estuvo abierta entre el 1 de julio y el 14 de agosto de 2026. Las respuestas institucionales a cada observación se publican en esta ficha.',
    'type' => Consultation::TYPE_IPT,
    'starts' => '2026-07-01 09:00',
    'ends' => '2026-08-14 23:59',
    'auth' => [Consultation::AUTH_GUEST],
    'creada' => '2026-06-22 09:30',
    'estados' => [['2026-06-30 17:45', Consultation::STATUS_ACTIVE], ['2026-08-17 09:10', Consultation::STATUS_CLOSED]],
    'docs' => [
        ['at' => '2026-06-26 11:00', 'title' => 'Memoria explicativa',
            'desc' => 'Fundamentos de la modificación y estudio hidráulico del río Aconcagua.',
            'file' => 'memoria-explicativa-pri-aconcagua.pdf',
            'make' => fn () => pdf('Memoria explicativa', $ctxC, [
                'La modificación responde al crecimiento urbano del valle y a los nuevos estudios hidráulicos del río Aconcagua, que muestran áreas de inundación más extensas que las consideradas en el plan vigente.',
                'Cambios propuestos. (1) Ajuste de los límites de las zonas de extensión urbana de San Felipe, Los Andes y Llay-Llay. (2) Actualización de las áreas de riesgo de inundación. (3) Reserva de fajas para vialidad intercomunal.',
            ])],
        ['at' => '2026-06-26 11:06', 'title' => 'Ordenanza propuesta',
            'desc' => 'Texto de la ordenanza con las normas de cada zona.',
            'file' => 'ordenanza-propuesta-pri-aconcagua.pdf',
            'make' => fn () => pdf('Ordenanza propuesta', $ctxC, [
                'Artículo 1. La presente ordenanza modifica las zonas de extensión urbana y las áreas de riesgo del Plan Regulador Intercomunal del Valle del Aconcagua.',
                'Artículo 2. En las áreas de riesgo de inundación no se permitirán nuevas edificaciones de uso habitacional, salvo que un estudio fundado demuestre la mitigación del riesgo.',
            ])],
    ],
    'envios' => [
        [
            'at' => '2026-07-03 18:22', 'ip' => '203.0.113.61', 'ua' => 'android',
            'quien' => ['tipo' => 'natural', 'nombre' => 'Hernán Tapia Silva', 'rut' => 9876543,
                'email' => 'hernan.tapia@example.com', 'comuna' => 'San Felipe', 'edad' => 63],
            'obs' => [
                ['ref' => 'c-ribera', 'tema' => 'Riesgo natural', 'asunto' => 'Viviendas en la ribera del río',
                    'texto' => 'La nueva zona de extensión urbana del sector poniente de San Felipe llega hasta la ribera del río. En el invierno de 2023 ese sector se inundó por completo. Pido que el límite se retire al menos hasta el camino ribereño y que el área quede como zona de riesgo.'],
            ],
        ],
        [
            'at' => '2026-07-09 12:40', 'ip' => '192.0.2.18', 'ua' => 'windows',
            'quien' => ['tipo' => 'pj', 'razon' => 'Agrícola Santa Elena Ltda.', 'fantasia' => 'Santa Elena', 'rut' => 77345120,
                'email' => 'contacto@agricolasantaelena.example.com', 'direccion' => 'Camino Internacional km 12, Los Andes'],
            'obs' => [
                ['ref' => 'c-suelos', 'tema' => 'Uso de suelo', 'asunto' => 'Protección de suelo agrícola de riego',
                    'texto' => 'Los predios de la parte baja del valle tienen suelos de clase I y II con derechos de agua. Solicitamos que no se incorporen a la zona de extensión urbana, porque su urbanización implicaría perder suelo agrícola de alto valor que no es recuperable.',
                    'adjunto' => ['informe-suelos-santa-elena.pdf', fn () => pdf('Informe de capacidad de uso de suelos', $ctxC, [
                        'Agrícola Santa Elena Ltda. acompaña este informe a su observación.',
                        'Los predios de la parte baja del valle presentan suelos de clase I y II de capacidad de uso, con riego asegurado por derechos de agua inscritos.',
                        'Su incorporación a la zona de extensión urbana implicaría la pérdida permanente de suelo agrícola de alto valor productivo.',
                    ])]],
            ],
        ],
        [
            'at' => '2026-07-15 20:05', 'ip' => '198.51.100.130', 'ua' => 'iphone',
            'quien' => ['tipo' => 'natural', 'nombre' => 'Camila Reyes Olivares', 'rut' => 19234567,
                'email' => 'camila.reyes@example.com', 'comuna' => 'Los Andes', 'edad' => 31],
            'obs' => [
                ['ref' => 'c-equipamiento', 'tema' => 'Equipamiento', 'asunto' => 'Salud y educación en las nuevas zonas',
                    'texto' => 'Las nuevas zonas de extensión urbana van a recibir miles de viviendas, pero el plan no reserva terrenos para consultorios ni colegios. Pido que se definan áreas de equipamiento antes de que se construya todo.'],
            ],
        ],
        [
            'at' => '2026-07-22 10:15', 'ip' => '203.0.113.205', 'ua' => 'mac',
            'quien' => ['tipo' => 'org', 'razon' => 'Coordinadora Ciudadana por el Río Aconcagua', 'rut' => 65234871,
                'email' => 'coordinadora.aconcagua@example.com', 'direccion' => 'Sede vecinal Villa El Sauce, Quillota'],
            'obs' => [
                ['ref' => 'c-parque', 'tema' => 'Areas verdes', 'asunto' => 'Parque ribereño',
                    'texto' => 'Proponemos que la franja de riesgo de inundación se destine a un parque ribereño continuo, con senderos y arborización nativa, en lugar de quedar como sitio eriazo. Así se protege a la población y se recupera el río para la comunidad.'],
            ],
        ],
        [
            'at' => '2026-08-05 16:30', 'ip' => '192.0.2.77', 'ua' => 'android',
            'quien' => ['tipo' => 'natural', 'nombre' => 'Luis Navarro Fuentes', 'rut' => 11987654,
                'email' => 'luis.navarro@example.com', 'telefono' => '+56 9 4471 0382', 'comuna' => 'Llay-Llay'],
            'obs' => [
                ['ref' => 'c-camiones', 'tema' => 'Vialidad', 'asunto' => 'Tránsito de camiones por el centro',
                    'texto' => 'Los camiones que van al puerto cruzan el centro de Llay-Llay. Pido que el plan reserve la faja para una vía de circunvalación antes de que el crecimiento urbano la haga imposible.'],
            ],
        ],
        [
            'at' => '2026-08-12 22:48', 'ip' => '198.51.100.58', 'ua' => 'windows',
            'quien' => ['tipo' => 'natural', 'nombre' => 'Patricia Gómez Vera', 'rut' => 15345678,
                'email' => 'patricia.gomez@example.com', 'comuna' => 'San Esteban', 'edad' => 52],
            'obs' => [
                ['ref' => 'c-adobe', 'tema' => 'Patrimonio', 'asunto' => 'Casas de adobe del sector rural',
                    'texto' => 'En el sector rural de San Esteban hay casas de adobe con corredores que son parte de la identidad del valle. Pido que la modificación no permita subdivisiones que obliguen a demolerlas.'],
            ],
        ],
    ],
    'respuestas' => [
        ['ref' => 'c-ribera', 'at' => '2026-08-20 10:00', 'publicar' => true,
            'texto' => 'Se acoge la observación. El límite de la zona de extensión urbana se ajustará al camino ribereño y el área entre el camino y el cauce quedará como zona de riesgo de inundación, conforme al estudio hidráulico actualizado.'.$firma],
        ['ref' => 'c-suelos', 'at' => '2026-08-22 15:30', 'publicar' => true,
            'texto' => 'Se acoge parcialmente la observación. Los predios con suelos de clase I y II quedarán fuera de la zona de extensión urbana, salvo aquellos que ya cuentan con urbanización aprobada. El detalle se incorpora en la memoria explicativa definitiva.'.$firma],
        ['ref' => 'c-equipamiento', 'at' => '2026-08-25 11:15', 'publicar' => true,
            'texto' => 'Se acoge la observación. La ordenanza definitiva exigirá que cada zona de extensión urbana destine una parte de su superficie a equipamiento de salud y educación, en coordinación con los municipios.'.$firma],
        ['ref' => 'c-parque', 'at' => '2026-08-27 16:00', 'publicar' => true,
            'texto' => 'Se acoge la observación como orientación. La franja de riesgo quedará como área verde intercomunal, lo que permite el desarrollo futuro de un parque ribereño. Su diseño corresponde a un proyecto posterior, que se coordinará con los municipios.'.$firma],
        ['ref' => 'c-camiones', 'at' => '2026-09-01 12:00', 'publicar' => true,
            'texto' => 'Se acoge la observación. El plan reservará la faja vial para una circunvalación al norte del área urbana de Llay-Llay, cuyo trazado definitivo dependerá del estudio de ingeniería correspondiente.'.$firma],
        ['ref' => 'c-adobe', 'at' => '2026-09-04 10:30', 'publicar' => true,
            'texto' => 'No se acoge la observación en este instrumento. La protección de inmuebles de valor patrimonial corresponde al plan regulador comunal o a una declaratoria específica. La observación será remitida a la Municipalidad de San Esteban para su consideración.'.$firma],
    ],
];

// --- D. Publicada: se anuncia, aun no recibe observaciones -----------------

$consultas[] = [
    'slug' => 'prot-imagen-objetivo',
    'title' => $ctxD,
    'summary' => 'Primera etapa de participación del PROT: la visión de desarrollo territorial de la región. Revisa los documentos antes de que se abra el periodo de observaciones.',
    'description' => "El Plan Regional de Ordenamiento Territorial (PROT) orienta el uso del territorio regional considerando criterios ambientales, sociales y económicos. En esta etapa se somete a consulta la imagen objetivo: la visión de cómo queremos que se desarrolle la región en las próximas décadas.\n\n"
        .'El periodo de observaciones comenzará el 9 de noviembre de 2026. Mientras tanto, puedes revisar el documento base y el calendario de actividades.',
    'type' => Consultation::TYPE_PROT,
    'starts' => '2026-11-09 09:00',
    'ends' => '2026-12-18 23:59',
    'auth' => [Consultation::AUTH_CLAVEUNICA, Consultation::AUTH_GUEST],
    'creada' => '2026-09-28 11:00',
    'estados' => [['2026-10-01 09:30', Consultation::STATUS_PUBLISHED]],
    'docs' => [
        ['at' => '2026-09-30 15:00', 'title' => 'Imagen objetivo — documento base',
            'desc' => 'Visión de desarrollo territorial y lineamientos estratégicos propuestos.',
            'file' => 'imagen-objetivo-prot-documento-base.pdf',
            'make' => fn () => pdf('Imagen objetivo — documento base', $ctxD, [
                'La imagen objetivo describe la región que se busca construir: un territorio con ciudades integradas, un borde costero accesible, cuencas hídricas protegidas y actividades productivas compatibles con el medio ambiente.',
                'Lineamientos. (1) Sistema de centros poblados equilibrado. (2) Resguardo de los recursos hídricos. (3) Protección de la biodiversidad y del paisaje. (4) Gestión del riesgo de desastres. (5) Infraestructura para la conectividad regional.',
            ])],
        ['at' => '2026-09-30 15:05', 'title' => 'Calendario de actividades de participación',
            'desc' => 'Fechas de las jornadas presenciales en cada provincia.',
            'file' => 'calendario-participacion-prot.pdf',
            'make' => fn () => pdf('Calendario de actividades de participación', $ctxD, [
                'Las jornadas presenciales se realizarán entre el 9 de noviembre y el 18 de diciembre de 2026 en las capitales provinciales de la región.',
                'En cada jornada se presentará la imagen objetivo y se recibirán observaciones, que serán ingresadas al expediente del proceso.',
            ])],
    ],
    'envios' => [],
    'respuestas' => [],
];

// ---------------------------------------------------------------------------
// Archivos para la parte en vivo (consulta B y una participacion)
// ---------------------------------------------------------------------------

if (isset($opts['archivos'])) {
    $dir = rtrim($opts['archivos'], '/\\');
    if (! is_dir($dir) && ! mkdir($dir, 0775, true)) {
        fwrite(STDERR, "ERROR: no se pudo crear {$dir}.\n");
        exit(1);
    }
    $ctxB = 'Modificación al PRI — Sector Borde Costero Norte';
    file_put_contents("{$dir}/memoria-explicativa-borde-costero-norte.pdf", pdf('Memoria explicativa', $ctxB, [
        'La modificación propone actualizar las zonas de riesgo y las áreas verdes del Plan Regulador Intercomunal en el sector del borde costero norte.',
        'Objetivos. (1) Incorporar las zonas de riesgo por tsunami. (2) Ampliar las áreas verdes junto a los esteros. (3) Asegurar accesos públicos a las playas.',
    ]));
    file_put_contents("{$dir}/memoria-explicativa-borde-costero-norte-v2.pdf", pdf('Memoria explicativa (versión 2)', $ctxB, [
        'La modificación propone actualizar las zonas de riesgo y las áreas verdes del Plan Regulador Intercomunal en el sector del borde costero norte.',
        'Objetivos. (1) Incorporar las zonas de riesgo por tsunami. (2) Ampliar las áreas verdes junto a los esteros. (3) Asegurar accesos públicos a las playas. (4) Reconocer las caletas de pescadores.',
        'Versión 2: agrega el objetivo 4, solicitado por los municipios.',
    ]));
    file_put_contents("{$dir}/foto-para-adjuntar.jpg", picture('photo', 'Acceso a la playa, sector norte'));
    echo "Archivos para la parte en vivo en {$dir}\n";
}

// ---------------------------------------------------------------------------
// Carga: una sola linea de tiempo para todas las consultas
// ---------------------------------------------------------------------------

$tz = config('app.timezone');
$disk = config('filesystems.default');
$causer = app(CauserResolver::class);
$stored = [];
$timeline = [];
$creadas = [];     // slug => Consultation
$docs = [];        // slug|archivo => ConsultationDocument vigente
$obsByRef = [];    // ref => Observation
$stats = [];       // slug => contadores para el resumen

$on = function (string $when, Closure $fn) use (&$timeline): void {
    $timeline[] = [$when, count($timeline), $fn];
};

$put = function (string $path, string $content) use ($disk, &$stored): void {
    if (! Storage::disk($disk)->put($path, $content)) {
        throw new RuntimeException("No se pudo guardar {$path} en el disco {$disk}.");
    }
    $stored[] = $path;
};

$storeDocument = function (Consultation $c, array $d, string $content, int $version, string $group) use ($put, $disk, $autor): ConsultationDocument {
    $path = "consultations/{$c->id}/{$group}/v{$version}/{$d['file']}";
    $put($path, $content);

    return ConsultationDocument::create([
        'consultation_id' => $c->id,
        'title' => $d['title'],
        'description' => $d['desc'],
        'original_filename' => $d['file'],
        'mime_type' => 'application/pdf',
        'size_bytes' => strlen($content),
        'storage_path' => $path,
        'storage_disk' => $disk,
        'file_group_id' => $group,
        'version' => $version,
        'sha256' => hash('sha256', $content),
        'uploaded_by' => $autor->id,
    ]);
};

$submit = function (Consultation $c, array $envio) use ($tz, $causer, $put, $disk, $ua, &$obsByRef, &$stats): void {
    $q = $envio['quien'];
    $at = Carbon::parse($envio['at'], $tz);

    if ($q['tipo'] === 'claveunica') {
        // Mismo alta que ClaveUnicaController::upsertUser(), minutos antes del envio.
        Carbon::setTestNow($at->copy()->subMinutes(3));
        $causer->setCauser(null);
        $citizen = User::firstOrNew(['national_id' => rut($q['rut'])]);
        if (! $citizen->exists) {
            $citizen->name = $q['nombre'];
            $citizen->last_name = $q['apellido'];
            $citizen->email = $q['email'];
            $citizen->password = Str::random(40);
            $citizen->role = User::ROLE_CITIZEN;
            $citizen->is_active = true;
            $citizen->email_verified_at = now();
        }
        $citizen->last_login_at = now();
        $citizen->save();
        Carbon::setTestNow($at);

        $causer->setCauser($citizen);
        $identity = [
            'user_id' => $citizen->id,
            'auth_method_used' => Observation::AUTH_CLAVEUNICA,
            'snapshot_actor_type' => Observation::ACTOR_NATURAL,
            'snapshot_id_type' => Observation::ID_TYPE_RUT,
            'snapshot_national_id' => $citizen->national_id,
            'snapshot_full_name' => trim($citizen->name.' '.$citizen->last_name),
            'snapshot_email' => $citizen->email,
        ];
    } else {
        $causer->setCauser(null);
        $base = [
            'user_id' => null,
            'auth_method_used' => Observation::AUTH_GUEST,
            'snapshot_actor_type' => $q['tipo'],
            'snapshot_email' => $q['email'],
            'snapshot_phone' => $q['telefono'] ?? null,
        ];
        $identity = $q['tipo'] === Observation::ACTOR_NATURAL
            ? $base + [
                'snapshot_id_type' => isset($q['pasaporte']) ? Observation::ID_TYPE_PASSPORT : Observation::ID_TYPE_RUT,
                'snapshot_national_id' => $q['pasaporte'] ?? rut($q['rut']),
                'snapshot_full_name' => $q['nombre'],
                'snapshot_comuna' => $q['comuna'] ?? null,
                'snapshot_age' => $q['edad'] ?? null,
            ]
            : $base + [
                'snapshot_legal_name' => $q['razon'],
                'snapshot_trade_name' => $q['fantasia'] ?? null,
                'snapshot_business_id' => rut($q['rut']),
                'snapshot_address' => $q['direccion'] ?? null,
            ];
    }

    $mimeByExt = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'pdf' => 'application/pdf'];
    $groupId = (string) Str::uuid();
    foreach ($envio['obs'] as $item) {
        $attachment = [];
        if (isset($item['adjunto'])) {
            [$name, $make] = $item['adjunto'];
            $content = $make();
            $ext = pathinfo($name, PATHINFO_EXTENSION);
            $path = "observations/{$c->id}/".Str::random(40).".{$ext}";
            $put($path, $content);
            $attachment = [
                'attachment_path' => $path,
                'attachment_disk' => $disk,
                'attachment_original_name' => $name,
                'attachment_mime_type' => $mimeByExt[$ext],
                'attachment_size_bytes' => strlen($content),
            ];
            $stats[$c->slug]['adjuntos']++;
        }

        $obs = Observation::create([
            'consultation_id' => $c->id,
            'submission_group_id' => $groupId,
            'subject' => $item['asunto'],
            'body' => $item['texto'],
            'category' => $item['tema'],
            'ip_address' => $envio['ip'],
            'user_agent' => $ua[$envio['ua']],
            ...$identity,
            ...$attachment,
        ]);
        $stats[$c->slug]['obs']++;
        if (isset($item['ref'])) {
            $obsByRef[$item['ref']] = $obs;
        }
    }
};

foreach ($consultas as $s) {
    $slug = $s['slug'];
    if (Consultation::withTrashed()->where('slug', $slug)->exists()) {
        echo "  omitida: '{$slug}' ya existe\n";
        continue;
    }
    $stats[$slug] = ['obs' => 0, 'adjuntos' => 0, 'envios' => count($s['envios']),
        'docs' => count($s['docs']), 'pub' => 0, 'borr' => 0];

    $on($s['creada'], function () use ($s, $slug, $tz, $causer, $autor, &$creadas) {
        $causer->setCauser($autor);
        $creadas[$slug] = Consultation::create([
            'slug' => $slug,
            'title' => $s['title'],
            'summary' => $s['summary'],
            'description' => $s['description'],
            'instrument_type' => $s['type'],
            'status' => Consultation::STATUS_DRAFT,
            'starts_at' => Carbon::parse($s['starts'], $tz),
            'ends_at' => Carbon::parse($s['ends'], $tz),
            'auth_methods' => $s['auth'],
            'created_by' => $autor->id,
            'updated_by' => $autor->id,
        ]);
    });

    foreach ($s['estados'] as [$when, $status]) {
        $on($when, function () use ($slug, $status, $causer, $autor, &$creadas) {
            $causer->setCauser($autor);
            $creadas[$slug]->update(['status' => $status, 'updated_by' => $autor->id]);
        });
    }

    foreach ($s['docs'] as $d) {
        $key = $slug.'|'.$d['file'];
        $on($d['at'], function () use ($slug, $d, $key, $causer, $autor, $storeDocument, &$creadas, &$docs) {
            $causer->setCauser($autor);
            $docs[$key] = $storeDocument($creadas[$slug], $d, ($d['make'])(), 1, (string) Str::uuid());
        });
        if (isset($d['v2'])) {
            $on($d['v2']['at'], function () use ($slug, $d, $key, $causer, $autor, $storeDocument, &$creadas, &$docs) {
                $causer->setCauser($autor);
                $previous = $docs[$key];
                $previous->delete();
                $docs[$key] = $storeDocument($creadas[$slug], $d, ($d['v2']['make'])(), $previous->version + 1, $previous->file_group_id);
            });
        }
    }

    foreach ($s['envios'] as $envio) {
        $on($envio['at'], function () use ($slug, $envio, $submit, &$creadas) {
            $submit($creadas[$slug], $envio);
        });
    }

    foreach ($s['respuestas'] as $r) {
        $stats[$slug][$r['publicar'] ? 'pub' : 'borr']++;
        $on($r['at'], function () use ($r, $causer, $autor, &$obsByRef) {
            $causer->setCauser($autor);
            InstitutionalResponse::create([
                'observation_id' => $obsByRef[$r['ref']]->id,
                'content' => $r['texto'],
                'responded_by' => $autor->id,
                'responded_at' => now(),
                'status' => $r['publicar'] ? InstitutionalResponse::STATUS_PUBLISHED : InstitutionalResponse::STATUS_DRAFT,
                'published_at' => $r['publicar'] ? now() : null,
            ]);
        });
    }
}

if ($timeline === []) {
    echo "Nada que cargar: las consultas de la demo ya existen.\n";
    exit(0);
}

// Orden cronologico; a igual hora, el orden en que se registraron.
usort($timeline, fn ($a, $b) => [$a[0], $a[1]] <=> [$b[0], $b[1]]);

DB::beginTransaction();

try {
    foreach ($timeline as [$when, , $fn]) {
        Carbon::setTestNow(Carbon::parse($when, $tz));
        $fn();
    }

    Carbon::setTestNow();
    $causer->setCauser(null);

    if ($dryRun) {
        DB::rollBack();
        foreach ($stored as $path) {
            Storage::disk($disk)->delete($path);
        }
    } else {
        DB::commit();
    }
} catch (Throwable $e) {
    Carbon::setTestNow();
    DB::rollBack();
    foreach ($stored as $path) {
        Storage::disk($disk)->delete($path);
    }
    fwrite(STDERR, 'ERROR: '.$e->getMessage()."\n  en ".$e->getFile().':'.$e->getLine()."\n");
    exit(1);
}

echo ($dryRun ? "DRY-RUN OK (todo revertido)\n" : "OK\n")."  disco {$disk}, autor #{$autor->id}\n";
foreach ($creadas as $slug => $c) {
    $st = $stats[$slug];
    echo "  [{$c->status}] {$slug}\n"
        ."      {$st['docs']} antecedentes, {$st['obs']} observaciones en {$st['envios']} envios ({$st['adjuntos']} con adjunto),"
        ." {$st['pub']} respuestas publicadas, {$st['borr']} borradores\n"
        .'      '.route('public.consultations.show', $slug)."\n";
}
