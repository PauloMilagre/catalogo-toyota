-- Os três modelos do Trabalho I. Os valores são ilustrativos (não vêm de
-- fonte oficial). A motorização do Corolla foi corrigida de 2.0 para 1.8,
-- que é o motor do Corolla Altis Hybrid vendido no Brasil.
INSERT INTO modelos
    (slug, nome, categoria, preco, motor, potencia_cv, cambio, consumo_km_l,
     porta_malas_l, carga_kg, descricao, imagem, imagem_alt, imagem_largura, imagem_altura, legenda)
VALUES
    ('corolla-altis-hybrid', 'Corolla Altis Hybrid', 'Sedã híbrido', 195000.00,
     '1.8 híbrido, 16 válvulas', 122, 'Automático e-CVT', 21.5, 470, NULL,
     'O Corolla híbrido combina um motor 1.8 a combustão com um motor elétrico que assume sozinho o trabalho em baixas velocidades. A bateria se recarrega durante as frenagens, sem precisar de tomada, o que explica o consumo de 21,5 km/l no ciclo urbano, superior ao da versão a gasolina.\n\nNa prática, é um sedã de uso diário que troca a resposta imediata de um motor turbo pela economia em trânsito parado. Quem roda majoritariamente em estrada tende a notar menos diferença, e quem enfrenta congestionamento todos os dias sente no fim do mês.',
     'corolla.jpg', 'Toyota Corolla branco, visto de frente, estacionado em área gramada', 760, 497,
     'Corolla Altis Hybrid na versão 2025, em branco pérola'),
    ('hilux-srx-4x4', 'Hilux SRX 4x4', 'Picape 4x4', 330000.00,
     '2.8 turbodiesel, 16 válvulas', 204, 'Automático de 6 marchas', 9.8, NULL, 1000,
     'A Hilux SRX combina um motor 2.8 turbodiesel com tração 4x4 e caixa reduzida, projetada para piso irregular sem abrir mão de conforto na estrada. O que sustenta o reboque e a subida com carga não é a potência de pico, e sim o torque disponível em baixas rotações.\n\nNa prática, é uma picape que atende dois usos que raramente convivem: trabalho durante a semana e viagem no fim de semana. O consumo de 9,8 km/l no ciclo urbano é o preço do peso e da tração integral.',
     'hilux.jpg', 'Toyota Hilux SRX cinza, vista de frente, em terreno de vegetação seca', 760, 428,
     'Hilux SRX 4x4 na versão 2025, em pintura cinza'),
    ('gr-supra', 'GR Supra', 'Esportivo', 620000.00,
     '3.0 seis cilindros em linha, turbo', 382, 'Automático de 8 marchas', 8.6, 290, NULL,
     'O GR Supra usa um motor 3.0 de seis cilindros em linha, turbo, montado longitudinalmente e com tração traseira. A distribuição de peso próxima de 50/50 entre os eixos define o comportamento em curva mais do que o número de cavalos.\n\nNa prática, é um cupê de dois lugares feito para dirigir, não para transportar. O porta-malas de 290 litros e o consumo de 8,6 km/l no ciclo urbano deixam claro que a prioridade do projeto está em outro lugar.',
     'supra.jpg', 'Toyota GR Supra branco, visto de três quartos pela frente, com montanhas ao fundo', 760, 475,
     'GR Supra na versão 2025, em branco');
