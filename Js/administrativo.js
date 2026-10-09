/* ============================================================
   administrativo.js
   ------------------------------------------------------------
   El CRUD Administrativo ya NO usa JavaScript personalizado.
   Ahora funciona con formularios POST tradicionales y los
   stored procedures de la base de datos:
     - Crear   -> sp_InsertarAdministrativo
     - Editar  -> sp_EditarAdministrativo
     - Eliminar-> sp_EliminarAdministrativo
   Los modales (nuevo, ver, editar, eliminar) se manejan con la
   API nativa de Bootstrap 5 mediante los atributos
   data-bs-toggle / data-bs-target (incluidos en el bundle).
   Este archivo se conserva vacío intencionalmente.
   ============================================================ */
