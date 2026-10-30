USE transport_management;

ALTER TABLE users
    MODIFY COLUMN role ENUM('SUPERADMIN', 'ADMIN', 'DISPATCHER') NOT NULL;

UPDATE users
SET role = 'ADMIN'
WHERE role = 'SUPERADMIN'
    AND LOWER(email) <> 'drilon123@gmail.com';

UPDATE users
SET role = 'SUPERADMIN'
WHERE LOWER(email) = 'drilon123@gmail.com';
