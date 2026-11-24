-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 25/11/2026 às 00:00
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `hina`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `animes`
--

CREATE TABLE `animes` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `sinopse` text DEFAULT NULL,
  `capa_url` varchar(500) NOT NULL,
  `ano_lancamento` int(11) DEFAULT NULL,
  `nota_imdb` decimal(3,1) DEFAULT 0.0,
  `trailer_url` varchar(255) DEFAULT NULL,
  `imdb_link` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `animes`
--

INSERT INTO `animes` (`id`, `titulo`, `sinopse`, `capa_url`, `ano_lancamento`, `nota_imdb`, `trailer_url`, `imdb_link`) VALUES
(1, 'Re:ZERO -Starting Life in Another World-', 'Na história, Subaru Natsuki é um estudante comum do ensino médio que está perdido em um mundo alternativo, onde é resgatado por uma linda garota de cabelos grisalhos. Ele fica perto dela para retribuir o favor, mas o destino que a carrega é maior do que Subaru pode imaginar. Os inimigos atacam um por um e ambos são mortos. Ele então descobre que tem o poder de retroceder a morte, de volta à época em que veio a este mundo. Mas só ele se lembra do que aconteceu desde então. Notas: - O primeiro episódio foi ao ar com uma duração de aproximadamente 50 minutos, em oposição ao episódio padrão de 25 minutos. - Na temporada de inverno de 2020, Re:ZERO foi retransmitido e reeditado para caber em um intervalo de uma hora. Esta edição incluiu o primeiro OVA e adicionou pequenas modificações em certas cenas. Também adicionou uma cena adicional no final do episódio final.', 'https://s4.anilist.co/file/anilistcdn/media/anime/cover/medium/bx21355-wRVUrGxpvIQQ.jpg', 2016, 8.1, 'https://www.youtube.com/watch?v=vFfXjuVA1Jk', 'https://anilist.co/anime/21355'),
(2, 'Demon Slayer: Kimetsu no Yaiba', 'É o Período Taisho no Japão. Tanjiro, um garoto de bom coração que ganha a vida vendendo carvão, encontra sua família massacrada por um demônio. Para piorar a situação, sua irmã mais nova, Nezuko, a única sobrevivente, foi transformada em um demônio. Embora devastado por esta dura realidade, Tanjiro resolve se tornar um “matador de demônios” para que possa transformar sua irmã novamente em humana e matar o demônio que massacrou sua família.', 'https://s4.anilist.co/file/anilistcdn/media/anime/cover/medium/bx101922-WBsBl0ClmgYL.jpg', 2019, 8.2, 'https://www.youtube.com/watch?v=6vMuWuWlW4I', 'https://anilist.co/anime/101922'),
(3, 'ONE PIECE', 'Gold Roger era conhecido como o Rei dos Piratas, o ser mais forte e mais infame que já navegou pela Grand Line. A captura e a morte de Roger pelo Governo Mundial provocaram uma mudança em todo o mundo. Suas últimas palavras antes de morrer revelaram a localização do maior tesouro do mundo, o One Piece. Foi essa revelação que deu início à Grande Era dos Piratas, homens que sonhavam em encontrar o One Piece (que promete uma quantidade ilimitada de riquezas e fama) e, possivelmente, o mais cobiçado dos títulos para quem o encontrasse: o título de Rei dos Piratas. Aí entra Monkey D. Luffy, um garoto de 17 anos que desafia sua definição padrão de pirata. Em vez da persona popular de um pirata perverso, endurecido e desdentado que saqueia vilas por diversão, o motivo de Luffy para ser um pirata é de pura admiração; a ideia de uma aventura emocionante e de conhecer pessoas novas e intrigantes, além de encontrar o One Piece, são seus motivos para se tornar um pirata. Seguindo os passos do seu herói de infância, Luffy e sua tripulação viajam pela Grand Line, vivenciando aventuras malucas, desvendando mistérios sombrios e enfrentando inimigos poderosos, tudo para chegar ao One Piece. *Isso inclui os seguintes episódios especiais: - Chopperman ao Resgate! Proteja a emissora de TV à beira-mar! (Episódio 336) – A dupla mais forte! A dura batalha de Luffy e Toriko! (Episódio 492) – Formação da equipe! Salve Chopper (Episódio 542) - A Colaboração Mais Forte da História vs. O Glutão do Mar (Episódio 590) - 20º Aniversário! Especial Romance Dawn (Episódio 907)', 'https://s4.anilist.co/file/anilistcdn/media/anime/cover/medium/bx21-ELSYx3yMPcKM.jpg', 1999, 8.7, NULL, 'https://anilist.co/anime/21'),
(4, 'Blue Box', 'Será que esses apaixonados por esportes diferentes têm alguma chance? Taiki Inomata adora badminton, mas tem um longo caminho a percorrer antes de poder chegar ao campeonato nacional. Quando Taiki vê a veterana Chinatsu Kano treinando com todo o empenho no time feminino de basquete, ele se apaixona perdidamente por ela. Depois que uma reviravolta inesperada aproxima os dois, os esportes podem não ser mais a primeira coisa em que pensam! Observação: a série foi transmitida com uma semana de antecedência na Netflix Japão, começando com o episódio 2 lançado junto com o episódio 1.', 'https://s4.anilist.co/file/anilistcdn/media/anime/cover/medium/bx170942-KKcLfQzV57nG.jpg', 2024, 8.1, 'https://www.youtube.com/watch?v=uvvYMBLY-w4', 'https://anilist.co/anime/170942'),
(5, 'Sword Art Online', 'Em um futuro próximo, um jogo de RPG online multijogador massivo de realidade virtual (VRMMORPG) chamado Sword Art Online foi lançado, no qual os jogadores controlam seus avatares com seus corpos usando uma tecnologia chamada NerveGear. Certo dia, os jogadores descobrem que não podem sair do jogo, pois o criador do jogo os mantém em cativeiro, a menos que cheguem ao 100º andar da torre do jogo e derrotem o chefe final. No entanto, se eles morrerem no jogo, morrerão na vida real. A luta deles pela sobrevivência começa agora...', 'https://s4.anilist.co/file/anilistcdn/media/anime/cover/medium/bx11757-SxYDUzdr9rh2.jpg', 2012, 7.0, 'https://www.youtube.com/watch?v=C8Jl_-b7ju0', 'https://anilist.co/anime/11757'),
(6, 'Hunter x Hunter (2011)', 'Um Hunter é alguém que viaja pelo mundo realizando todo tipo de tarefas perigosas. Desde capturar criminosos até explorar as profundezas de terras desconhecidas em busca de tesouros perdidos. Gon é um menino cujo pai desapareceu há muito tempo, sendo um Caçador. Ele acredita que, se também pudesse seguir o caminho do pai, um dia poderia se reunir com ele. Depois de completar 12 anos, Gon sai de casa e assume a tarefa de participar do Exame Hunter, famoso por sua baixa taxa de aprovação e alta probabilidade de morte, para se tornar um Hunter oficial. No exame, ele faz amizade com Kurapika, movido pela vingança, Leorio, que quer ser médico, e Killua, um ex-assassino rebelde. A amizade deles prevalece ao longo das muitas provações e ameaças que enfrentam ao embarcar na perigosa carreira de Hunter.', 'https://s4.anilist.co/file/anilistcdn/media/anime/cover/medium/bx11061-y5gsT1hoHuHw.png', 2011, 8.9, 'https://www.youtube.com/watch?v=d6kBeJjTGnY', 'https://anilist.co/anime/11061'),
(7, 'JoJo\'s Bizarre Adventure: Stardust Crusaders - Battle in Egypt', 'Depois de 30 dias de viagem pelo mundo, assolados por inúmeras batalhas, Jotaro e seus companheiros finalmente chegaram ao Egito! Agora, apenas os usuários de \"Stand\" mais fortes permanecem para impedir a marcha final de nossos heróis ao Cairo: os Nove Deuses Egípcios, os servos mais zelosos de Dio. A linhagem Joestar cairá diante do vampiro monstruoso, ou Jotaro conseguirá derrotar Dio e salvar a vida de sua mãe?', 'https://s4.anilist.co/file/anilistcdn/media/anime/cover/medium/bx20799-S1eyqBDlx51E.jpg', 2015, 8.3, 'https://www.youtube.com/watch?v=kaSio11XVhM', 'https://anilist.co/anime/20799'),
(8, 'Frieren: Beyond Journey’s End', 'A aventura acabou, mas a vida continua para uma maga elfa que está apenas começando a aprender o que significa viver. A maga elfa Frieren e seus corajosos companheiros de aventura derrotaram o Rei Demônio e trouxeram a paz à terra. Mas Frieren viverá muito mais tempo do que o restante de seu antigo grupo. Como ela vai entender o que a vida significa para as pessoas ao seu redor? Décadas após a vitória, o funeral de um de seus amigos confronta Frieren com sua própria quase imortalidade. Frieren decide cumprir os últimos desejos de seus companheiros e acaba embarcando em uma nova aventura...', 'https://s4.anilist.co/file/anilistcdn/media/anime/cover/medium/bx154587-qQTzQnEJJ3oB.jpg', 2023, 9.0, 'https://www.youtube.com/watch?v=tR8YH0G67Rk', 'https://anilist.co/anime/154587'),
(9, 'Fullmetal Alchemist: Brotherhood', '\"In order for something to be obtained, something of equal value must be lost.\" Alchemy is bound by this Law of Equivalent Exchange—something the young brothers Edward and Alphonse Elric only realize after attempting human transmutation: the one forbidden act of alchemy. They pay a terrible price for their transgression—Edward loses his left leg, Alphonse his physical body. It is only by the desperate sacrifice of Edward\'s right arm that he is able to affix Alphonse\'s soul to a suit of armor. Devastated and alone, it is the hope that they would both eventually return to their original bodies that gives Edward the inspiration to obtain metal limbs called \"automail\" and become a state alchemist, the Fullmetal Alchemist. Three years of searching later, the brothers seek the Philosopher\'s Stone, a mythical relic that allows an alchemist to overcome the Law of Equivalent Exchange. Even with military allies Colonel Roy Mustang, Lieutenant Riza Hawkeye, and Lieutenant Colonel Maes Hughes on their side, the brothers find themselves caught up in a nationwide conspiracy that leads them not only to the true nature of the elusive Philosopher\'s Stone, but their country\'s murky history as well. In between finding a serial killer and racing against time, Edward and Alphonse must ask themselves if what they are doing will make them human again... or take away their humanity.', 'https://s4.anilist.co/file/anilistcdn/media/anime/cover/medium/bx5114-nSWCgQlmOMtj.jpg', 2009, 9.0, 'https://www.youtube.com/watch?v=1ac3_YdSSy0', 'https://anilist.co/anime/5114'),
(10, 'JUJUTSU KAISEN', 'A boy fights... for \"the right death.\" Hardship, regret, shame: the negative feelings that humans feel become Curses that lurk in our everyday lives. The Curses run rampant throughout the world, capable of leading people to terrible misfortune and even death. What\'s more, the Curses can only be exorcised by another Curse. Itadori Yuji is a boy with tremendous physical strength, though he lives a completely ordinary high school life. One day, to save a friend who has been attacked by Curses, he eats the finger of the Double-Faced Specter, taking the Curse into his own soul. From then on, he shares one body with the Double-Faced Specter. Guided by the most powerful of sorcerers, Gojou Satoru, Itadori is admitted to the Tokyo Metropolitan Technical High School of Sorcery, an organization that fights the Curses... and thus begins the heroic tale of a boy who became a Curse to exorcise a Curse, a life from which he could never turn back. Note: The first episode received an early web premiere on September 19th, 2020. The regular TV broadcast started on October 3rd, 2020.', 'https://s4.anilist.co/file/anilistcdn/media/anime/cover/medium/bx113415-LHBAeoZDIsnF.jpg', 2020, 8.4, 'https://www.youtube.com/watch?v=RIyb52EMx8c', 'https://anilist.co/anime/113415');

-- --------------------------------------------------------

--
-- Estrutura para tabela `comentarios_anime`
--

CREATE TABLE `comentarios_anime` (
  `id` int(11) NOT NULL,
  `anime_id` int(11) NOT NULL,
  `nome_usuario` varchar(100) NOT NULL,
  `nota` int(11) DEFAULT NULL CHECK (`nota` between 1 and 5),
  `comentario` text NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `episodios`
--

CREATE TABLE `episodios` (
  `id` int(11) NOT NULL,
  `anime_id` int(11) DEFAULT NULL,
  `numero_episodio` int(11) NOT NULL,
  `titulo_episodio` varchar(255) NOT NULL,
  `link_download` varchar(500) NOT NULL,
  `video_url` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `favoritos_usuario`
--

CREATE TABLE `favoritos_usuario` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `anime_id` int(11) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `favoritos_usuario`
--

INSERT INTO `favoritos_usuario` (`id`, `usuario_id`, `anime_id`, `criado_em`) VALUES
(1, 3, 7, '2026-11-20 21:11:52');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `tipo` varchar(30) NOT NULL DEFAULT 'usuario',
  `nome` varchar(120) NOT NULL DEFAULT '',
  `foto_perfil` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `email`, `senha`, `tipo`, `nome`, `foto_perfil`) VALUES
(1, 'Paulohenriqueepdias@gmail.com', '$2y$12$.k4oDlPGUbV1q3mPCtx35.ZHLESTv.BlMAJmjrls7aAIznXLzK26O', 'admin', '', NULL),
(2, 'pauloyuke@gmail.com', '$2y$12$fZ5HwNPGHz2e609j./efReAbTsWIHIMBAufsgZ6TDhH/RGa821WJa', 'usuario', '', NULL),
(3, 'paulo@gmail.com', '$2y$12$MZod9AardFPe55P/ifipoeltkKtKF2y8Ts4AeDgn3uO6jUXm5f0he', 'usuario', 'paulo', 'uploads/avatars/user_3_1795209089.png');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `animes`
--
ALTER TABLE `animes`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `comentarios_anime`
--
ALTER TABLE `comentarios_anime`
  ADD PRIMARY KEY (`id`),
  ADD KEY `anime_id` (`anime_id`);

--
-- Índices de tabela `episodios`
--
ALTER TABLE `episodios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `anime_id` (`anime_id`);

--
-- Índices de tabela `favoritos_usuario`
--
ALTER TABLE `favoritos_usuario`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_usuario_anime` (`usuario_id`,`anime_id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `animes`
--
ALTER TABLE `animes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `comentarios_anime`
--
ALTER TABLE `comentarios_anime`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `episodios`
--
ALTER TABLE `episodios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `favoritos_usuario`
--
ALTER TABLE `favoritos_usuario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `comentarios_anime`
--
ALTER TABLE `comentarios_anime`
  ADD CONSTRAINT `comentarios_anime_ibfk_1` FOREIGN KEY (`anime_id`) REFERENCES `animes` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `episodios`
--
ALTER TABLE `episodios`
  ADD CONSTRAINT `episodios_ibfk_1` FOREIGN KEY (`anime_id`) REFERENCES `animes` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
