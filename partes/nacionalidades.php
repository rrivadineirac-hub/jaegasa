<?php
/**
 * ============================================================
 * nacionalidades.php  —  Lista de nacionalidades (gentilicios)
 * Sistema Académico JAEGASA
 * ------------------------------------------------------------
 * Uso en una vista:
 *   <select name="nacionalidad">
 *       <?= opcionesNacionalidades($valorActual) ?>
 *   </select>
 * ============================================================
 */

if (!function_exists('opcionesNacionalidades')) {

    /**
     * Devuelve los <option> con todas las nacionalidades del mundo.
     * @param string $seleccionado Valor a marcar como selected (por defecto Boliviana).
     */
    function opcionesNacionalidades(string $seleccionado = 'Boliviana'): string
    {
        $lista = [
            'Afgana', 'Alemana', 'Argelina', 'Andorrana', 'Angoleña', 'Antiguana',
            'Saudí', 'Argentina', 'Armenia', 'Australiana', 'Austriaca', 'Azerbaiyana',
            'Bahameña', 'Bangladesí', 'Barbadense', 'Baréiní', 'Belga', 'Beliceña',
            'Beninesa', 'Bielorrusa', 'Birmana', 'Boliviana', 'Bosnia', 'Botsuana',
            'Brasileña', 'Bruneana', 'Búlgara', 'Burkinesa', 'Burundesa', 'Butanesa',
            'Caboverdiana', 'Camboyana', 'Camerunesa', 'Canadiense', 'Catarí', 'Chadiana',
            'Chilena', 'China', 'Chipriota', 'Colombiana', 'Comorense', 'Congoleña',
            'Norcoreana', 'Surcoreana', 'Marfileña', 'Costarricense', 'Croata', 'Cubana',
            'Danesa', 'Dominiquesa', 'Ecuatoriana', 'Egipcia', 'Salvadoreña', 'Emiratí',
            'Eritrea', 'Eslovaca', 'Eslovena', 'Española', 'Estadounidense', 'Estonia',
            'Etíope', 'Filipina', 'Finlandesa', 'Fiyiana', 'Francesa', 'Gabonesa',
            'Gambiana', 'Georgiana', 'Ghanesa', 'Granadina', 'Griega', 'Guatemalteca',
            'Guineana', 'Ecuatoguineana', 'Bisauguineana', 'Guyanesa', 'Haitiana',
            'Hondureña', 'Húngara', 'India', 'Indonesia', 'Iraquí', 'Iraní', 'Irlandesa',
            'Islandesa', 'Israelí', 'Italiana', 'Jamaicana', 'Japonesa', 'Jordana',
            'Kazaja', 'Keniana', 'Kirguisa', 'Kiribatiana', 'Kuwaití', 'Laosiana',
            'Lesotense', 'Letona', 'Libanesa', 'Liberiana', 'Libia', 'Liechtensteiniana',
            'Lituana', 'Luxemburguesa', 'Macedonia', 'Malgache', 'Malasia', 'Malauí',
            'Maldiva', 'Maliense', 'Maltesa', 'Marroquí', 'Marshalesa', 'Mauriciana',
            'Mauritana', 'Mexicana', 'Micronesia', 'Moldava', 'Monegasca', 'Mongola',
            'Montenegrina', 'Mozambiqueña', 'Namibia', 'Nauruana', 'Nepalí', 'Nicaragüense',
            'Nigerina', 'Nigeriana', 'Noruega', 'Neozelandesa', 'Omaní', 'Neerlandesa',
            'Pakistaní', 'Palauana', 'Palestina', 'Panameña', 'Papú', 'Paraguaya',
            'Peruana', 'Polaca', 'Portuguesa', 'Británica', 'Centroafricana', 'Checa',
            'Dominicana', 'Ruandesa', 'Rumana', 'Rusa', 'Samoana', 'Sancristobaleña',
            'Sanmarinense', 'Sanvicentina', 'Santalucense', 'Santotomense', 'Senegalesa',
            'Serbia', 'Seychellense', 'Sierraleonesa', 'Singapurense', 'Siria', 'Somalí',
            'Ceilanesa', 'Suazi', 'Sudafricana', 'Sudanesa', 'Sursudanesa', 'Sueca',
            'Suiza', 'Surinamesa', 'Tailandesa', 'Tanzana', 'Tayika', 'Timorense',
            'Togolesa', 'Tongana', 'Trinitense', 'Tunecina', 'Turcomana', 'Turca',
            'Tuvaluana', 'Ucraniana', 'Ugandesa', 'Uruguaya', 'Uzbeka', 'Vanuatuense',
            'Vaticana', 'Venezolana', 'Vietnamita', 'Yemení', 'Yibutiana', 'Zambiana',
            'Zimbabuense',
        ];

        sort($lista);

        $html = '<option value="">Seleccione...</option>';
        foreach ($lista as $nac) {
            $sel = ($nac === $seleccionado) ? ' selected' : '';
            $val = htmlspecialchars($nac, ENT_QUOTES, 'UTF-8');
            $html .= "<option value=\"{$val}\"{$sel}>{$val}</option>";
        }
        return $html;
    }
}
