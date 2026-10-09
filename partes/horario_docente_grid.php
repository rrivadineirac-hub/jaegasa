<?php
/**
 * partes/horario_docente_grid.php — Componente reutilizable para la cuadrícula horaria del docente
 * Recibe: $horarioSemanalDocente, $periodosDocente, $idHorario (opcional)
 */

$e = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
$diasGrid = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes'];
$diasMap = [
    'Lunes'     => 'Lunes',
    'Martes'    => 'Martes',
    'Miercoles' => 'Miércoles',
    'Jueves'    => 'Jueves',
    'Viernes'   => 'Viernes'
];

$numDiaHoy = (int) date('w'); // 0: Dom, 1: Lun, 2: Mar, 3: Mie, 4: Jue, 5: Vie, 6: Sab
$diaHoyActual = match ($numDiaHoy) {
    1 => 'Lunes',
    2 => 'Martes',
    3 => 'Miercoles',
    4 => 'Jueves',
    5 => 'Viernes',
    default => ''
};

$idHorarioSeleccionado = (int) ($idHorario ?? 0);
$nombreDocenteActual = $nombreDocente ?? $docenteNombre ?? $_SESSION['nombreCompleto'] ?? $_SESSION['nombre'] ?? '';

// Pre-cálculo de celdas con rowspan para fusionar períodos consecutivos del mismo curso/materia en el mismo día
// teniendo cuidado de NO fusionar a través de las filas de RECREO.
$matrixCells = [];
if (!empty($periodosDocente)) {
    foreach ($diasGrid as $dia) {
        $i = 0;
        $n = count($periodosDocente);
        while ($i < $n) {
            $per = $periodosDocente[$i];
            if ((int)$per['esRecreo'] === 1) {
                $matrixCells[$dia][$i] = ['recreo' => true];
                $i++;
                continue;
            }

            $clase = $horarioSemanalDocente[$dia][(int)$per['idPeriodo']] ?? null;

            if ($clase === null) {
                $matrixCells[$dia][$i] = ['skip' => false, 'rowspan' => 1, 'clase' => null];
                $i++;
                continue;
            }

            $keyCurrent = $clase['idCurso'] . '_' . $clase['idMateria'];
            $span = 1;
            $j = $i + 1;
            while ($j < $n) {
                $perNext = $periodosDocente[$j];
                if ((int)$perNext['esRecreo'] === 1) {
                    break; // DETENER: no se puede juntar a través del RECREO
                }

                $claseNext = $horarioSemanalDocente[$dia][(int)$perNext['idPeriodo']] ?? null;
                if ($claseNext === null) {
                    break;
                }

                $keyNext = $claseNext['idCurso'] . '_' . $claseNext['idMateria'];
                if ($keyNext === $keyCurrent) {
                    $span++;
                    $j++;
                } else {
                    break;
                }
            }

            $matrixCells[$dia][$i] = ['skip' => false, 'rowspan' => $span, 'clase' => $clase];

            for ($k = $i + 1; $k < $i + $span; $k++) {
                $matrixCells[$dia][$k] = ['skip' => true, 'rowspan' => 0, 'clase' => null];
            }

            $i += $span;
        }
    }
}
?>

<link rel="stylesheet" href="<?= BASE_URL ?>Css/horario.css?v=<?= @filemtime(BASE_PATH . '/Css/horario.css') ?>">
<style>
    .grid-docente-container {
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        border: 1px solid rgba(0,0,0,0.06);
        padding: 20px;
        margin-bottom: 24px;
    }
    .grid-docente-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 18px;
        flex-wrap: wrap;
        gap: 12px;
    }
    .dia-hoy-col {
        background-color: rgba(21, 128, 61, 0.05) !important;
        border-left: 2px solid rgba(22, 101, 52, 0.3) !important;
        border-right: 2px solid rgba(22, 101, 52, 0.3) !important;
    }
    .th-dia-hoy {
        background-color: #15803d !important;
        color: #ffffff !important;
        position: relative;
    }
    .badge-hoy {
        background: #ffeb3b;
        color: #000;
        font-size: 0.65rem;
        font-weight: 800;
        padding: 2px 6px;
        border-radius: 6px;
        margin-left: 5px;
        vertical-align: middle;
        text-transform: uppercase;
    }
    
    /* Estilos de tarjetas de clase por color de día */
    .card-clase-docente-grid {
        border-radius: 12px;
        padding: 12px;
        transition: all 0.28s cubic-bezier(0.4, 0, 0.2, 1);
        text-decoration: none !important;
        color: inherit;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        height: 100%;
        min-height: 95px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.04);
        position: relative;
        overflow: hidden;
    }

    /* Colores por día */
    .card-clase-docente-grid.color-lunes {
        background: #f7fee7;
        border: 1.5px solid #bef264;
    }
    .card-clase-docente-grid.color-martes {
        background: #f5f3ff;
        border: 1.5px solid #ddd6fe;
    }
    .card-clase-docente-grid.color-miercoles {
        background: #fefce8;
        border: 1.5px solid #fef08a;
    }
    .card-clase-docente-grid.color-jueves {
        background: #ecfeff;
        border: 1.5px solid #a5f3fc;
    }
    .card-clase-docente-grid.color-viernes {
        background: #fff1f2;
        border: 1.5px solid #fecdd3;
    }

    /* Clases de HOY (Clickables con Efecto Hover Impactante) */
    .card-clase-docente-grid.card-es-hoy {
        cursor: pointer !important;
        border-width: 2px;
    }
    .card-clase-docente-grid.card-es-hoy:hover {
        transform: translateY(-4px) scale(1.03);
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
        filter: brightness(1.02);
    }
    .card-clase-docente-grid.card-es-hoy.color-lunes:hover { border-color: #65a30d; box-shadow: 0 0 15px rgba(101, 163, 13, 0.4); }
    .card-clase-docente-grid.card-es-hoy.color-martes:hover { border-color: #7c3aed; box-shadow: 0 0 15px rgba(124, 58, 237, 0.4); }
    .card-clase-docente-grid.card-es-hoy.color-miercoles:hover { border-color: #d97706; box-shadow: 0 0 15px rgba(217, 119, 6, 0.4); }
    .card-clase-docente-grid.card-es-hoy.color-jueves:hover { border-color: #0891b2; box-shadow: 0 0 15px rgba(8, 145, 178, 0.4); }
    .card-clase-docente-grid.card-es-hoy.color-viernes:hover { border-color: #e11d48; box-shadow: 0 0 15px rgba(225, 29, 72, 0.4); }

    /* Clases de OTROS DÍAS (No clickables, sin hover) */
    .card-clase-docente-grid.card-no-hoy {
        cursor: not-allowed !important;
        opacity: 0.82;
        user-select: none;
    }
    .card-clase-docente-grid.card-no-hoy:hover {
        transform: none !important;
        box-shadow: 0 2px 6px rgba(0,0,0,0.04) !important;
    }

    /* Estado Seleccionado */
    .card-clase-docente-grid.seleccionada {
        border-width: 2.5px !important;
        border-color: #2563eb !important;
        box-shadow: 0 0 14px rgba(37, 99, 235, 0.4) !important;
    }

    /* Tipografía del Curso (Arriba y Grande) */
    .curso-titulo-head {
        font-size: 1.05rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.2;
        letter-spacing: -0.2px;
    }
    .materia-subtitulo {
        font-size: 0.82rem;
        font-weight: 600;
        color: #475569;
        margin-top: 2px;
    }

    .celda-libre {
        min-height: 95px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #9ca3af;
        font-size: 0.78rem;
        font-weight: 500;
        border: 1px dashed #e5e7eb;
        border-radius: 12px;
        background: #fafafa;
    }

    /* CONFIGURACIÓN PARA IMPRIMIR EN 1 SOLA HOJA HORIZONTAL */
    @page {
        size: landscape;
        margin: 5mm;
    }
    @media print {
        *, *::before, *::after {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
        }
        .sidebar, .topbar, .dash-header, .card-panel, .modal, .btn, .no-print, button, .btn-toggle, nav, header, footer {
            display: none !important;
        }
        html, body {
            width: 100% !important;
            height: 100% !important;
            max-height: 100vh !important;
            margin: 0 !important;
            padding: 0 !important;
            background: #ffffff !important;
            overflow: hidden !important;
        }
        .app-layout, .app-main, .app-content, .container-fluid, .container {
            display: block !important;
            width: 100% !important;
            height: auto !important;
            max-height: 100vh !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: visible !important;
        }
        .grid-docente-container {
            width: 100% !important;
            max-height: 98vh !important;
            padding: 4px !important;
            margin: 0 !important;
            border: none !important;
            box-shadow: none !important;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }
        .grid-docente-header {
            margin-bottom: 4px !important;
        }
        .grid-docente-header .text-muted {
            font-size: 8pt !important;
            color: #475569 !important;
        }
        .grid-docente-header h4 {
            font-size: 13pt !important;
            font-weight: 800 !important;
            color: #000000 !important;
            margin-bottom: 0 !important;
        }
        .table-responsive {
            overflow: visible !important;
            max-height: none !important;
        }
        table.timetable-table {
            width: 100% !important;
            table-layout: fixed !important;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
            border-collapse: collapse !important;
        }
        table.timetable-table th, table.timetable-table td {
            border: 1px solid #000000 !important;
            padding: 2px 3px !important;
        }
        table.timetable-table th {
            font-size: 8.5pt !important;
            padding: 3px 4px !important;
        }
        table.timetable-table td {
            font-size: 7.5pt !important;
            height: auto !important;
        }
        .card-clase-docente-grid {
            min-height: 0 !important;
            padding: 4px 6px !important;
            box-shadow: none !important;
            border: 1px solid #64748b !important;
        }
        .card-clase-docente-grid:hover {
            transform: none !important;
            box-shadow: none !important;
        }
        .curso-titulo-head {
            font-size: 8.5pt !important;
            font-weight: 800 !important;
        }
        .materia-subtitulo {
            font-size: 7.5pt !important;
            font-weight: 600 !important;
        }
        .celda-libre {
            min-height: 25px !important;
            font-size: 7pt !important;
            padding: 2px !important;
        }
        .badge-hoy {
            display: none !important;
        }
    }
</style>

<div class="grid-docente-container">
    <div class="grid-docente-header align-items-center">
        <div>
            <div class="text-muted fw-bold text-uppercase" style="font-size: 0.82rem; letter-spacing: 1px;">HORARIO DEL</div>
            <h4 class="fw-bold mb-0 text-jaegasa text-uppercase" style="letter-spacing: -0.3px;">
                PROF. <?= $e(mb_strtoupper(trim($nombreDocenteActual), 'UTF-8')) ?>
            </h4>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-success text-white fw-bold d-inline-flex align-items-center gap-1 shadow-sm no-print" onclick="window.print()" title="Imprimir Horario Semanal">
                <i class="bi bi-printer-fill fs-6"></i> Imprimir
            </button>
            <?php if (!empty($diaHoyActual)): ?>
                <span class="badge bg-success px-3 py-2 fs-6 shadow-sm">
                    <i class="bi bi-calendar-check-fill"></i> Hoy es <strong><?= $diasMap[$diaHoyActual] ?></strong>
                </span>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-responsive">
        <table class="timetable-table align-middle">
            <thead>
                <tr>
                    <th style="width: 110px;">Hora / Período</th>
                    <?php foreach ($diasGrid as $dia): ?>
                        <?php 
                        $esHoy = ($dia === $diaHoyActual); 
                        $diaNormalizado = strtolower($dia);
                        ?>
                        <th class="th-color-<?= $diaNormalizado ?> <?= $esHoy ? 'th-dia-hoy' : '' ?>">
                            <?= $diasMap[$dia] ?>
                            <?php if ($esHoy): ?>
                                <span class="badge-hoy">HOY</span>
                            <?php endif; ?>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($periodosDocente)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="bi bi-info-circle"></i> No hay períodos escolares registrados.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($periodosDocente as $idx => $per): ?>
                        <?php if ((int)$per['esRecreo'] === 1): ?>
                            <!-- Fila de RECREO -->
                            <tr class="timetable-row-recreo">
                                <td class="timetable-period-col">
                                    <span class="timetable-period-num">RECREO</span>
                                    <span class="timetable-period-time">
                                        <?= date('H:i', strtotime($per['horaInicio'])) ?> - <?= date('H:i', strtotime($per['horaFin'])) ?>
                                    </span>
                                </td>
                                <td colspan="5" class="text-center align-middle fw-bold text-muted" style="background:#fef3c7; color:#92400e !important;">
                                    <i class="bi bi-cup-hot-fill"></i> RECREO ESCOLAR (<?= date('H:i', strtotime($per['horaInicio'])) ?> - <?= date('H:i', strtotime($per['horaFin'])) ?>)
                                </td>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <!-- Columna Hora / Período -->
                                <td class="timetable-period-col">
                                    <span class="timetable-period-num"><?= (int)$per['numero'] ?></span>
                                    <span class="timetable-period-time">
                                        <?= date('H:i', strtotime($per['horaInicio'])) ?> - <?= date('H:i', strtotime($per['horaFin'])) ?>
                                    </span>
                                </td>

                                <!-- Columnas por Día -->
                                <?php foreach ($diasGrid as $dia): ?>
                                    <?php 
                                    $cellInfo = $matrixCells[$dia][$idx] ?? ['skip' => false, 'rowspan' => 1, 'clase' => null];
                                    if (!empty($cellInfo['skip'])) {
                                        // Omitir este <td> porque fue fusionado por rowspan en un período anterior
                                        continue;
                                    }

                                    $rowspan = (int)($cellInfo['rowspan'] ?? 1);
                                    $clase = $cellInfo['clase'];
                                    $esHoy = ($dia === $diaHoyActual);
                                    $diaNorm = strtolower($dia);
                                    $esSeleccionada = ($clase && (int)$clase['idHorario'] === $idHorarioSeleccionado);
                                    ?>
                                    <td class="<?= $esHoy ? 'dia-hoy-col' : '' ?>" <?= $rowspan > 1 ? 'rowspan="' . $rowspan . '"' : '' ?> style="width: 180px; min-width: 170px; max-width: 190px;">
                                        <?php if ($clase): ?>
                                            <?php 
                                            $cursoDesc = $clase['cursoNombre'] . ' "' . $clase['paraleloNombre'] . '"';
                                            $urlAsistencia = BASE_URL . 'index.php?c=asistencia_docente&idHorario=' . (int)$clase['idHorario'] . '#seccionAsistencia';
                                            ?>

                                            <?php if ($esHoy): ?>
                                                <!-- Clase de HOY -> CLICKABLE con Efecto Hover -->
                                                <a href="<?= $urlAsistencia ?>" 
                                                   class="card-clase-docente-grid color-<?= $diaNorm ?> card-es-hoy <?= $esSeleccionada ? 'seleccionada' : '' ?>"
                                                   title="Haga clic para pasar lista hoy en <?= $e($cursoDesc) ?>">
                                                    <div>
                                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                                            <div class="curso-titulo-head">
                                                                <?= $e($cursoDesc) ?>
                                                            </div>
                                                            <div class="d-flex flex-column align-items-end gap-1">
                                                                <span class="badge bg-dark ms-1" style="font-size: 0.65rem;">
                                                                    <?= $e($clase['materiaSigla']) ?>
                                                                </span>
                                                                <?php if ($rowspan > 1): ?>
                                                                    <span class="badge bg-warning text-dark ms-1" style="font-size: 0.6rem;">
                                                                        <?= $rowspan ?> Horas
                                                                    </span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                        <div class="materia-subtitulo">
                                                            <i class="bi bi-book-half"></i> <?= $e($clase['materiaNombre']) ?>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex justify-content-between align-items-center mt-2 text-muted" style="font-size: 0.72rem;">
                                                        <span><i class="bi bi-door-closed"></i> <?= $e($clase['aula'] ?: 'Sin aula') ?></span>
                                                        <span class="badge bg-success text-white fw-bold"><i class="bi bi-check2-square"></i> Lista</span>
                                                    </div>
                                                </a>
                                            <?php else: ?>
                                                <!-- Clase de OTRO DÍA -> NO CLICKABLE -->
                                                <div class="card-clase-docente-grid color-<?= $diaNorm ?> card-no-hoy <?= $esSeleccionada ? 'seleccionada' : '' ?>"
                                                     title="Solo se permite registrar asistencia el día correspondiente (<?= $diasMap[$dia] ?>)">
                                                    <div>
                                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                                            <div class="curso-titulo-head text-secondary">
                                                                <?= $e($cursoDesc) ?>
                                                            </div>
                                                            <div class="d-flex flex-column align-items-end gap-1">
                                                                <span class="badge bg-secondary ms-1" style="font-size: 0.65rem;">
                                                                    <?= $e($clase['materiaSigla']) ?>
                                                                </span>
                                                                <?php if ($rowspan > 1): ?>
                                                                    <span class="badge bg-secondary ms-1" style="font-size: 0.6rem;">
                                                                        <?= $rowspan ?> Horas
                                                                    </span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                        <div class="materia-subtitulo text-muted">
                                                            <i class="bi bi-book-half"></i> <?= $e($clase['materiaNombre']) ?>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex justify-content-between align-items-center mt-2 text-muted" style="font-size: 0.72rem;">
                                                        <span><i class="bi bi-door-closed"></i> <?= $e($clase['aula'] ?: 'Sin aula') ?></span>
                                                        <span class="badge bg-light text-muted border"><i class="bi bi-lock-fill"></i> <?= $diasMap[$dia] ?></span>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                        <?php else: ?>
                                            <div class="celda-libre">
                                                <span>Libre</span>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
