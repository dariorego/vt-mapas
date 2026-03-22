-- Migration 001: Adiciona colunas de rota na tabela viagem
-- Data: 2026-03-22
-- Descrição: Suporte ao Google Directions API — polyline, distância e tempo total por viagem

ALTER TABLE viagem
    ADD COLUMN polyline_encoded   TEXT         NULL COMMENT 'Rota codificada Google Encoded Polyline',
    ADD COLUMN distancia_total_km DECIMAL(8,2) NULL COMMENT 'Distância total da viagem em km',
    ADD COLUMN tempo_total_min    INT          NULL COMMENT 'Tempo estimado total em minutos';
