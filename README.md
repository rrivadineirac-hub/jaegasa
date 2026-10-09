# jaegasa - Sistema Académico

Sistema web de gestión académica desarrollado en PHP y MySQL para el control integral de estudiantes, docentes, tutores, asistencias, calificaciones y reportes con generación de códigos QR y PDF.

## Características

- **Gestión Académica**: Control de estudiantes, docentes, tutores, niveles, grados y secciones.
- **Asistencia**: Registro y seguimiento diario de asistencias de estudiantes.
- **Credenciales y Reportes**: Generación de credenciales con código QR y reportes en formato PDF (usando FPDF).
- **Importación de Datos**: Módulo para importar estudiantes y registros masivos.
- **Seguridad y Control de Acceso**: Autenticación de usuarios por roles (Administrador, Docente, etc.).

## Tecnologías Utilizadas

- **PHP** (PDO, arquitectura MVC ligera)
- **MySQL / MariaDB**
- **JavaScript / AJAX / HTML5 / CSS3**
- **Composer** (`setasign/fpdf`, `chillerlan/php-qrcode`)

## Requisitos e Instalación

1. Clonar o descargar el repositorio en la carpeta `htdocs` de XAMPP:
   ```bash
   git clone https://github.com/rrivadineirac-hub/jaegasa.git
   ```
2. Importar los scripts SQL de la base de datos (`reportes_Pdf.sql` / `reportes_mysql_workbench.sql`) en phpMyAdmin o MySQL Workbench.
3. Verificar la configuración de conexión a la base de datos en `Config/Conexion.php`:
   ```php
   private const HOST = 'localhost';
   private const DB   = 'jaegasa_db_fluter';
   private const USER = 'root';
   private const PASS = '';
   ```
4. Iniciar los servicios Apache y MySQL en XAMPP.
5. Acceder desde el navegador a: `http://localhost/SistemaAcademico/`
