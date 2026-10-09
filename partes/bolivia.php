<?php
/**
 * ============================================================
 * bolivia.php  —  Departamentos y provincias de Bolivia
 * Sistema Académico JAEGASA
 * ------------------------------------------------------------
 * Provee:
 *   - opcionesDepartamentos($sel)  -> <option> de los 9 departamentos
 *   - Un <script> con la data de provincias y la lógica en cascada:
 *       * Muestra/oculta departamento y provincia según nacionalidad
 *         (solo visibles si la nacionalidad es "Boliviana").
 *       * Llena las provincias según el departamento elegido.
 *
 * Convenciones esperadas en el formulario:
 *   - select name="nacionalidad"
 *   - select name="departamento"  dentro de un contenedor .ubicacion-bolivia
 *   - select name="provincia"     dentro de un contenedor .ubicacion-bolivia
 *     y con atributo data-actual="<provincia guardada>" (para edición)
 * ============================================================
 */

$DEPARTAMENTOS_PROVINCIAS = [
    'La Paz' => ['Murillo', 'Omasuyos', 'Pacajes', 'Camacho', 'Muñecas', 'Larecaja',
                 'Franz Tamayo', 'Ingavi', 'Loayza', 'Inquisivi', 'Sud Yungas', 'Los Andes',
                 'Aroma', 'Nor Yungas', 'Abel Iturralde', 'Bautista Saavedra', 'Manco Kapac',
                 'Gualberto Villarroel', 'José Manuel Pando', 'Caranavi'],
    'Santa Cruz' => ['Andrés Ibáñez', 'Warnes', 'Velasco', 'Ichilo', 'Chiquitos', 'Sara',
                     'Cordillera', 'Vallegrande', 'Florida', 'Obispo Santistevan',
                     'Ñuflo de Chávez', 'Ángel Sandóval', 'Manuel María Caballero',
                     'Germán Busch', 'Guarayos'],
    'Cochabamba' => ['Cercado', 'Campero', 'Ayopaya', 'Esteban Arce', 'Arani', 'Arque',
                     'Capinota', 'Germán Jordán', 'Quillacollo', 'Chapare', 'Tapacarí',
                     'Carrasco', 'Mizque', 'Punata', 'Bolívar', 'Tiraque'],
    'Oruro' => ['Cercado', 'Eduardo Abaroa', 'Carangas', 'Sajama', 'Litoral', 'Poopó',
                'Pantaleón Dalence', 'Ladislao Cabrera', 'Sabaya', 'Saucarí', 'Tomás Barrón',
                'Sud Carangas', 'San Pedro de Totora', 'Sebastián Pagador', 'Mejillones',
                'Nor Carangas'],
    'Potosí' => ['Tomás Frías', 'Rafael Bustillo', 'Cornelio Saavedra', 'Chayanta', 'Charcas',
                 'Nor Chichas', 'Alonso de Ibáñez', 'Sud Chichas', 'Nor Lípez', 'Sud Lípez',
                 'José María Linares', 'Antonio Quijarro', 'Bernardino Bilbao', 'Daniel Campos',
                 'Modesto Omiste', 'Enrique Baldivieso'],
    'Chuquisaca' => ['Oropeza', 'Azurduy', 'Zudáñez', 'Tomina', 'Hernando Siles', 'Yamparáez',
                     'Nor Cinti', 'Belisario Boeto', 'Sud Cinti', 'Luis Calvo'],
    'Tarija' => ['Cercado', 'Aniceto Arce', 'Gran Chaco', 'Eustaquio Méndez', 'José María Avilés',
                 'Burnet O\'Connor'],
    'Beni' => ['Cercado', 'Vaca Díez', 'José Ballivián', 'Yacuma', 'Moxos', 'Marbán',
               'Mamoré', 'Iténez'],
    'Pando' => ['Nicolás Suárez', 'Manuripi', 'Madre de Dios', 'Abuná', 'Federico Román'],
];

if (!function_exists('opcionesDepartamentos')) {
    function opcionesDepartamentos(string $sel = ''): string
    {
        global $DEPARTAMENTOS_PROVINCIAS;
        $html = '<option value="">Seleccione...</option>';
        foreach (array_keys($DEPARTAMENTOS_PROVINCIAS) as $dep) {
            $s = ($dep === $sel) ? ' selected' : '';
            $v = htmlspecialchars($dep, ENT_QUOTES, 'UTF-8');
            $html .= "<option value=\"{$v}\"{$s}>{$v}</option>";
        }
        return $html;
    }
}
?>
<script>
window.PROVINCIAS_BOLIVIA = <?= json_encode($DEPARTAMENTOS_PROVINCIAS, JSON_UNESCAPED_UNICODE) ?>;

document.addEventListener('DOMContentLoaded', function () {
    const PROV = window.PROVINCIAS_BOLIVIA || {};

    document.querySelectorAll('form').forEach(function (form) {
        const nac  = form.querySelector('[name="nacionalidad"]');
        const dep  = form.querySelector('[name="departamento"]');
        const prov = form.querySelector('[name="provincia"]');
        if (!nac || !dep || !prov) return;

        const contenedores = form.querySelectorAll('.ubicacion-bolivia');

        function toggleBolivia() {
            const esBolivia = (nac.value === 'Boliviana');
            contenedores.forEach(function (c) {
                c.style.display = esBolivia ? '' : 'none';
            });
        }

        function llenarProvincias(preseleccion) {
            const provincias = PROV[dep.value] || [];
            prov.innerHTML = '<option value="">Seleccione...</option>';
            provincias.forEach(function (p) {
                const opt = document.createElement('option');
                opt.value = p;
                opt.textContent = p;
                if (p === preseleccion) opt.selected = true;
                prov.appendChild(opt);
            });
        }

        // Inicialización (respeta valores guardados al editar)
        llenarProvincias(prov.getAttribute('data-actual') || '');
        toggleBolivia();

        nac.addEventListener('change', toggleBolivia);
        dep.addEventListener('change', function () { llenarProvincias(''); });
    });
});
</script>
