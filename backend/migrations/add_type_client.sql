-- Ajout du type de client : particulier (TTC) ou entreprise (HT + TVA 17 %)
ALTER TABLE clients 
ADD COLUMN type_client VARCHAR(20) NOT NULL DEFAULT 'particulier';
