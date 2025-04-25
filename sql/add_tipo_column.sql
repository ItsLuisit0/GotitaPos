-- Añadir columna tipo a la tabla movimientocaja
ALTER TABLE movimientocaja ADD COLUMN tipo ENUM('ingreso', 'egreso') NOT NULL DEFAULT 'ingreso' AFTER caja_id; 