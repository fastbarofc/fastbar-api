-- Enche uma CÓPIA DE TESTE do banco com o volume de alguns anos de uso.
-- NUNCA rodar no banco de verdade nem no banco do dia a dia (fastbar_api).
-- Uso (MariaDB, que tem as tabelas seq_1_to_N):
--   1. mariadb-dump fastbar_api > backup.sql
--   2. create database fastbar_volume; mariadb fastbar_volume < backup.sql
--   3. mariadb fastbar_volume < scripts/desempenho/encher-volume.sql
-- Parte do banco que sai do seeder (200 eventos, 20 clientes) e chega em:
-- 18 mil eventos, 15 mil clientes, 144 mil parcelas a receber, 108 mil contas a pagar,
-- 270 mil drinks de evento, 270 mil linhas de histórico, 3 mil colaboradores, 20 mil leads.

set foreign_key_checks = 0;
set unique_checks = 0;
set @hoje = curdate();

-- Trava de segurança: só roda num banco com "volume" ou "teste" no nome.
set @ok = if(database() like '%volume%' or database() like '%teste%', 1, 0);
set @sql = if(@ok, 'select "banco de teste, seguindo" as aviso', 'signal sqlstate "45000" set message_text = "Este script so roda em banco de teste (nome com volume ou teste)"');
prepare trava from @sql; execute trava; deallocate prepare trava;

-- ---------------------------------------------------------------- clientes (20 -> 15 mil)
create temporary table t as select c.*, s.seq from clients c join seq_1_to_749 s where c.id <= 20;
update t set id = id + seq * 100, name = concat(name, ' ', seq), email = concat('c', id, '@teste.local'),
       created_at = now() - interval floor(rand() * 2100) day;
alter table t drop column seq;
insert into clients select * from t;
drop temporary table t;

create temporary table t as select a.*, s.seq from addresses a join seq_1_to_749 s
 where a.addressable_type = 'App\\Models\\Client' and a.addressable_id <= 20;
update t set id = id + seq * 1000, addressable_id = addressable_id + seq * 100;
alter table t drop column seq;
insert into addresses select * from t;
drop temporary table t;

-- ---------------------------------------------------------------- fornecedores (20 -> 2 mil) e locais (50 -> 1 mil)
create temporary table t as select x.*, s.seq from suppliers x join seq_1_to_99 s where x.id <= 20;
update t set id = id + seq * 100, fantasy_name = concat(fantasy_name, ' ', seq), corporate_name = concat(corporate_name, ' ', seq);
alter table t drop column seq;
insert into suppliers select * from t;
drop temporary table t;

create temporary table t as select a.*, s.seq from addresses a join seq_1_to_99 s
 where a.addressable_type = 'App\\Models\\Supplier' and a.addressable_id <= 20;
update t set id = 1000000 + id + seq * 1000, addressable_id = addressable_id + seq * 100;
alter table t drop column seq;
insert into addresses select * from t;
drop temporary table t;

create temporary table t as select x.*, s.seq from event_places x join seq_1_to_19 s where x.id <= 50;
update t set id = id + seq * 100, name = concat(name, ' ', seq), slug = concat(slug, '-', seq);
alter table t drop column seq;
insert into event_places select * from t;
drop temporary table t;

create temporary table t as select a.*, s.seq from addresses a join seq_1_to_19 s
 where a.addressable_type = 'App\\Models\\EventPlace' and a.addressable_id <= 50;
update t set id = 2000000 + id + seq * 1000, addressable_id = addressable_id + seq * 100;
alter table t drop column seq;
insert into addresses select * from t;
drop temporary table t;

-- ---------------------------------------------------------------- colaboradores (1 mil -> 3 mil)
create temporary table t as select x.*, s.seq from collaborators x join seq_1_to_2 s where x.id < 1000;
update t set id = id + seq * 1000, name = concat(name, ' ', seq), user_id = null, has_login = 0;
alter table t drop column seq;
insert into collaborators select * from t;
drop temporary table t;

create temporary table t as select x.*, s.seq from collaborator_companies x join seq_1_to_2 s where x.id < 10000;
update t set id = id + seq * 10000, collaborator_id = collaborator_id + seq * 1000;
alter table t drop column seq;
insert into collaborator_companies select * from t;
drop temporary table t;

-- ---------------------------------------------------------------- eventos (200 -> 18 mil), espalhados em 6 anos
-- Evento novo = evento do seeder + seq * 1000. Passado fica "Concluído" (85%) ou perdido; futuro mantém a situação.
create temporary table t as select e.*, s.seq from events e join seq_1_to_89 s where e.id <= 200;
update t set id = id + seq * 1000,
       name = concat(name, ' ', seq),
       date = date - interval (seq * 24 + floor(rand() * 20)) day,
       client_id = 1 + floor(rand() * 20) + 100 * floor(rand() * 750),
       event_place_id = 1 + floor(rand() * 50) + 100 * floor(rand() * 20),
       old_system_id = null,
       contract_body = repeat(concat('<p>Cláusula de teste do contrato do evento ', id, ' com texto longo para simular o peso real. </p>'), 200);
update t set event_status_id = if(rand() < 0.85, 5, elt(1 + floor(rand() * 3), 6, 7, 9)),
       closed_at = date, created_at = date - interval 200 day, updated_at = date
 where date < @hoje;
alter table t drop column seq;
insert into events select * from t;
drop temporary table t;

create temporary table t as select x.*, s.seq from client_event x join seq_1_to_89 s where x.id <= 600 and x.event_id <= 200;
update t set id = id + seq * 1000, event_id = event_id + seq * 1000, client_id = 1 + floor(rand() * 20) + 100 * floor(rand() * 750);
alter table t drop column seq;
insert into client_event select * from t;
drop temporary table t;

create temporary table t as select x.*, s.seq from event_notes x join seq_1_to_89 s where x.id <= 600 and x.event_id <= 200;
update t set id = id + seq * 1000, event_id = event_id + seq * 1000;
alter table t drop column seq;
insert into event_notes select * from t;
drop temporary table t;

create temporary table t as select x.*, s.seq from event_material x join seq_1_to_89 s where x.id <= 2000 and x.event_id <= 200;
update t set id = id + seq * 10000, event_id = event_id + seq * 1000;
alter table t drop column seq;
insert into event_material select * from t;
drop temporary table t;

create temporary table t as select x.*, s.seq from extra_sales x join seq_1_to_89 s where x.id <= 600 and x.event_id <= 200;
update t set id = id + seq * 1000, event_id = event_id + seq * 1000;
alter table t drop column seq;
insert into extra_sales select * from t;
drop temporary table t;

-- ---------------------------------------------------------------- parcelas a receber (8 por evento = 144 mil)
-- Realista: vencidas há mais de 45 dias estão pagas; só os últimos meses ficam em aberto.
insert into receivables (description, amount, due_date, paid_at, received_amount, status, payment_method_id,
                         receivableable_type, receivableable_id, created_at, updated_at)
select concat('Parcela ', k.seq, '/8 do evento #', e.id), round(coalesce(e.total_value, 8000) / 8, 2),
       @d := e.date - interval ((8 - k.seq) * 30) day,
       if(@d < @hoje - interval 45 day, @d + interval floor(rand() * 5) day, null),
       if(@d < @hoje - interval 45 day, round(coalesce(e.total_value, 8000) / 8, 2), null),
       if(@d < @hoje - interval 45 day, 'paid', 'pending'),
       1 + floor(rand() * 5), 'App\\Models\\Event', e.id, e.created_at, e.updated_at
  from events e join seq_1_to_8 k where e.id > 1000;

-- ---------------------------------------------------------------- contas a pagar (6 por evento = 108 mil)
insert into payables (event_id, description, amount, due_date, paid_at, paid_amount, payment_method_id, status,
                      approval_status, financial_operation_id, supplier_id, created_at, updated_at)
select e.id, concat('Despesa ', k.seq, ' do evento #', e.id), round(200 + rand() * 3000, 2),
       @d := e.date + interval (cast(k.seq as signed) * 7 - 20) day,
       if(@d < @hoje - interval 45 day, @d, null),
       null,
       1 + floor(rand() * 5),
       if(@d < @hoje - interval 45 day, 'paid', 'pending'),
       'approved', 21 + floor(rand() * 25), 1 + floor(rand() * 20) + 100 * floor(rand() * 100), e.created_at, e.updated_at
  from events e join seq_1_to_6 k where e.id > 1000;
update payables set paid_amount = amount where status = 'paid' and event_id > 1000;

-- ---------------------------------------------------------------- pagamentos do fechamento (3 por evento passado)
insert into event_payments (event_id, parthenon_bank_account_id, payment_method_id, value, date, created_at, updated_at)
select e.id, 1 + floor(rand() * 5), 1 + floor(rand() * 5), round(300 + rand() * 4000, 2), e.date - interval (k.seq * 20) day, e.created_at, e.updated_at
  from events e join seq_1_to_3 k where e.id > 1000 and e.date < @hoje;

-- ---------------------------------------------------------------- menu de drinks (3 pastas x 5 drinks por evento = 270 mil)
insert into event_kits (id, event_kit_id, event_id, name, value, themed_table, ordination, optional, checklist, selected, is_item, created_at, updated_at)
select 100000 + e.id * 3 + k.seq, null, e.id, concat('Pasta ', k.seq), round(rand() * 900, 2), 0, k.seq, 0, 0, 1, 0, e.created_at, e.updated_at
  from events e join seq_1_to_3 k where e.id > 1000;

create temporary table tp as select id, (@n := @n + 1) n from products, (select @n := 0) z where deleted_at is null;
set @produtos = (select count(*) from tp);
insert into event_kit_product (event_kit_id, product_id, value, ordination, optional, quantity, checklist, selected, created_at, updated_at)
select ek.id, (select id from tp where n = 1 + ((ek.id * 7 + k.seq * 13) % @produtos)), round(rand() * 60, 2), k.seq, 0, 1 + floor(rand() * 50), 0, 1, ek.created_at, ek.updated_at
  from event_kits ek join seq_1_to_5 k where ek.id > 100000;
drop temporary table tp;

-- ---------------------------------------------------------------- histórico (15 linhas por evento = 270 mil)
insert into audits (user_type, user_id, event, auditable_type, auditable_id, old_values, new_values, url, ip_address, created_at, updated_at)
select 'App\\Models\\User', elt(1 + floor(rand() * 3), 1, 2, 18), 'updated', 'App\\Models\\Event', e.id,
       concat('{"event_guests":', 80 + k.seq, '}'), concat('{"event_guests":', 81 + k.seq, '}'),
       concat('http://localhost:8000/api/events/', e.id), '127.0.0.1',
       @c := e.created_at + interval (k.seq * 10) day, @c
  from events e join seq_1_to_15 k where e.id > 1000;

-- ---------------------------------------------------------------- estoque (1.200 -> 60 mil movimentos)
create temporary table t as select x.*, s.seq from stock_movements x join seq_1_to_49 s where x.id < 100000;
update t set id = id + seq * 100000, created_at = now() - interval floor(rand() * 2100) day;
alter table t drop column seq;
insert into stock_movements select * from t;
drop temporary table t;

-- ---------------------------------------------------------------- comercial: 20 mil leads, 60 mil atividades
-- Antigos estão ganhos ou perdidos; só os dos últimos 3 meses estão andando no funil.
insert into leads (name, phone, email, city, source, temperature, event_date, guests, estimated_value, stage, position,
                   user_id, lost_reason_id, next_contact_date, stage_changed_at, first_contact_at, created_at, updated_at)
select concat('Lead de teste ', s.seq), concat('1199', lpad(s.seq, 7, '0')), concat('lead', s.seq, '@teste.local'), 'São Paulo',
       elt(1 + floor(rand() * 6), 'whatsapp', 'instagram', 'site', 'indicacao', 'cerimonial', 'trafego_pago'),
       elt(1 + floor(rand() * 3), 'quente', 'morno', 'frio'),
       (@c := now() - interval floor(pow(rand(), 0.6) * 2100) day) + interval 240 day,
       50 + floor(rand() * 400), round(5000 + rand() * 40000, 2),
       @st := if(@c < now() - interval 90 day, if(rand() < 0.3, 'ganho', 'perdido'),
                 elt(1 + floor(rand() * 9), 'novo', 'em_contato', 'reuniao', 'proposta_enviada', 'follow_up', 'negociacao', 'aguardando_assinatura', 'nutricao', 'perdido')),
       s.seq, elt(1 + floor(rand() * 4), 1, 2, 18, 19),
       if(@st = 'perdido', 1 + floor(rand() * 19), null),
       if(@st in ('ganho', 'perdido'), null, @hoje + interval floor(rand() * 10 - 3) day),
       @c + interval 5 day, @c, @c, @c + interval 5 day
  from seq_1_to_20000 s;

insert into lead_activities (lead_id, user_id, type, result, happened_at, notes, created_at, updated_at)
select l.id, l.user_id, elt(1 + floor(rand() * 4), 'whatsapp', 'ligacao', 'email', 'reuniao'),
       elt(1 + floor(rand() * 2), 'realizado', 'sem_resposta'), l.created_at + interval k.seq day, 'Contato de teste', l.created_at, l.created_at
  from leads l join seq_1_to_3 k where l.name like 'Lead de teste %';

-- ---------------------------------------------------------------- compromissos (6 mil) e licitações (500), com resumo de IA de ~6 KB
insert into appointments (title, description, summary, summary_status, date, start_time, end_time, client_id, created_by, updated_by, created_at, updated_at)
select concat('Reunião de teste ', s.seq), 'Compromisso de teste',
       if(s.seq % 3 = 0, repeat('Resumo da reunião gerado pela IA, com vários parágrafos de texto. ', 90), null),
       if(s.seq % 3 = 0, 'done', null),
       @hoje + interval (60 - floor(rand() * 1500)) day, '10:00:00', '11:00:00', 1 + floor(rand() * 20), elt(1 + floor(rand() * 3), 1, 2, 18), 1, now(), now()
  from seq_1_to_6000 s;
insert into appointment_user (appointment_id, user_id, created_at, updated_at)
select a.id, a.created_by, now(), now() from appointments a where a.title like 'Reunião de teste %';

insert into biddings (agency, notice_number, opening_date, opening_time, status, observation, summary, created_at, updated_at)
select concat('Prefeitura de teste ', s.seq), concat(s.seq, '/2026'), @hoje + interval (30 - floor(rand() * 900)) day, '09:00:00',
       elt(1 + floor(rand() * 5), 'prevista', 'em_andamento', 'ganha', 'perdida', 'cancelada'), 'Licitação de teste', repeat('Resumo do edital gerado pela IA, com exigências e prazos. ', 140), now(), now()
  from seq_1_to_500 s;

-- ---------------------------------------------------------------- comunicação: 3 mil conversas, 90 mil mensagens
insert into messaging_accounts (channel, name, external_id, display, active, created_at, updated_at)
select 'whatsapp', 'Conta de teste (volume)', 'teste-volume', '11 90000-0000', 1, now(), now()
 where not exists (select 1 from messaging_accounts where external_id = 'teste-volume');
set @conta = (select id from messaging_accounts where external_id = 'teste-volume');

insert into conversations (messaging_account_id, channel, contact_external_id, contact_name, contact_phone, user_id, status, internal,
                           last_message_at, last_direction, last_message_preview, unread_count, created_at, updated_at)
select @conta, 'whatsapp', concat('teste-', s.seq), concat('Contato de teste ', s.seq), concat('1198', lpad(s.seq, 7, '0')),
       elt(1 + floor(rand() * 3), 1, 2, 18), 'open', 0,
       @c := now() - interval floor(rand() * 400 * 24 * 60) minute, 'in', 'Olá, gostaria de um orçamento', floor(rand() * 3), @c, @c
  from seq_1_to_3000 s;

insert into messages (conversation_id, direction, type, body, status, sent_at, created_at, updated_at)
select c.id, if(k.seq % 2, 'in', 'out'), 'text', concat('Mensagem de teste ', k.seq, ' com um texto de tamanho normal de conversa.'), 'delivered',
       c.last_message_at - interval (30 - k.seq) hour, c.created_at, c.created_at
  from conversations c join seq_1_to_30 k where c.contact_external_id like 'teste-%';

set foreign_key_checks = 1;
set unique_checks = 1;

select 'events' tabela, count(*) linhas from events union all select 'clients', count(*) from clients
union all select 'receivables', count(*) from receivables union all select 'payables', count(*) from payables
union all select 'event_kit_product', count(*) from event_kit_product union all select 'audits', count(*) from audits
union all select 'collaborators', count(*) from collaborators union all select 'leads', count(*) from leads
union all select 'messages', count(*) from messages;
