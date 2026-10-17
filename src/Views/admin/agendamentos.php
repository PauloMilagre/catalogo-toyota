<?php
/**
 * Painel do administrador: todos os agendamentos, com filtro por status.
 *
 * @var \Toyota\Core\View $this
 * @var list<array>       $agendamentos
 * @var array             $meta
 * @var ?string           $statusFiltro
 * @var list<string>      $todosStatus
 */
use Toyota\Services\RegrasAgendamento;
?>
<section class="pagina" aria-labelledby="titulo-admin">
  <h1 id="titulo-admin">Agendamentos</h1>

  <form class="filtro" method="get" action="<?= $this->e($this->url('/admin/agendamentos')) ?>">
    <div class="campo">
      <label for="status">Status</label>
      <select id="status" name="status">
        <option value="">Todos</option>
        <?php foreach ($todosStatus as $s): ?>
          <option value="<?= $this->e($s) ?>"<?= $statusFiltro === $s ? ' selected' : '' ?>><?= $this->e($this->status($s)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button type="submit" class="botao botao--pequeno">Filtrar</button>
  </form>

  <p><?= (int) $meta['total'] ?> agendamento(s) encontrado(s).</p>

  <?php if ($agendamentos !== []): ?>
    <div class="tabela-rolagem">
      <table class="tabela">
        <caption class="visualmente-oculto">Agendamentos de test-drive de todos os clientes</caption>
        <thead>
          <tr>
            <th scope="col">Nº</th>
            <th scope="col">Cliente</th>
            <th scope="col">Modelo</th>
            <th scope="col">Data e período</th>
            <th scope="col">Observações</th>
            <th scope="col">Status</th>
            <th scope="col">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($agendamentos as $a): ?>
            <?php $proximos = RegrasAgendamento::proximosStatus($a['status'], 'admin'); ?>
            <tr>
              <td><?= (int) $a['id'] ?></td>
              <td>
                <?= $this->e($a['usuario_nome']) ?><br>
                <span class="texto-suave"><?= $this->e($a['usuario_email']) ?></span>
                <?php if ($a['telefone']): ?><br><span class="texto-suave"><?= $this->e($a['telefone']) ?></span><?php endif; ?>
              </td>
              <td><?= $this->e($a['modelo_nome']) ?></td>
              <td><?= $this->e($this->data($a['data'])) ?><br><span class="texto-suave"><?= $this->e($this->periodo($a['periodo'])) ?></span></td>
              <td class="tabela__texto"><?= nl2br($this->e($a['observacoes'] ?? '')) ?></td>
              <td><span class="status status--<?= $this->e($a['status']) ?>"><?= $this->e($this->status($a['status'])) ?></span></td>
              <td>
                <?php if ($proximos !== []): ?>
                  <form class="acoes" method="post"
                        action="<?= $this->e($this->url('/admin/agendamentos/' . (int) $a['id'] . '/status')) ?>">
                    <?= $this->campoCsrf() ?>
                    <label class="visualmente-oculto" for="novo-status-<?= (int) $a['id'] ?>">Novo status do nº <?= (int) $a['id'] ?></label>
                    <select id="novo-status-<?= (int) $a['id'] ?>" name="status">
                      <?php foreach ($proximos as $s): ?>
                        <option value="<?= $this->e($s) ?>"><?= $this->e($this->status($s)) ?></option>
                      <?php endforeach; ?>
                    </select>
                    <button type="submit" class="botao botao--pequeno">Salvar</button>
                  </form>
                <?php endif; ?>
                <form class="acoes" method="post"
                      action="<?= $this->e($this->url('/admin/agendamentos/' . (int) $a['id'] . '/excluir')) ?>">
                  <?= $this->campoCsrf() ?>
                  <button type="submit" class="botao botao--secundario botao--pequeno">Excluir</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?= $this->parcial('paginacao', [
        'meta'    => $meta,
        'caminho' => '/admin/agendamentos',
        'filtros' => $statusFiltro !== null ? ['status' => $statusFiltro] : [],
    ]) ?>
  <?php endif; ?>
</section>
