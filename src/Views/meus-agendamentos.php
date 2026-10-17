<?php
/**
 * @var \Toyota\Core\View $this
 * @var list<array>       $agendamentos
 * @var array             $meta
 * @var string            $hoje
 */
use Toyota\Services\RegrasAgendamento;
?>
<section class="pagina" aria-labelledby="titulo-meus">
  <h1 id="titulo-meus">Meus agendamentos</h1>

  <?php if ($agendamentos === []): ?>
    <p>Você ainda não tem test-drives agendados.</p>
    <p><a class="botao" href="<?= $this->e($this->url('/agendar')) ?>">Agendar test-drive</a></p>
  <?php else: ?>
    <div class="tabela-rolagem">
      <table class="tabela">
        <caption class="visualmente-oculto">Seus test-drives, do mais recente para o mais antigo</caption>
        <thead>
          <tr>
            <th scope="col">Nº</th>
            <th scope="col">Modelo</th>
            <th scope="col">Data</th>
            <th scope="col">Período</th>
            <th scope="col">Status</th>
            <th scope="col">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($agendamentos as $a): ?>
            <tr>
              <td><?= (int) $a['id'] ?></td>
              <td><?= $this->e($a['modelo_nome']) ?></td>
              <td><?= $this->e($this->data($a['data'])) ?></td>
              <td><?= $this->e($this->periodo($a['periodo'])) ?></td>
              <td><span class="status status--<?= $this->e($a['status']) ?>"><?= $this->e($this->status($a['status'])) ?></span></td>
              <td>
                <?php if (in_array($a['status'], RegrasAgendamento::STATUS_ATIVOS, true) && $a['data'] > $hoje): ?>
                  <form class="acoes" method="post"
                        action="<?= $this->e($this->url('/agendamentos/' . (int) $a['id'] . '/cancelar')) ?>">
                    <?= $this->campoCsrf() ?>
                    <button type="submit" class="botao botao--secundario botao--pequeno">Cancelar</button>
                  </form>
                <?php else: ?>
                  —
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?= $this->parcial('paginacao', ['meta' => $meta, 'caminho' => '/meus-agendamentos']) ?>
  <?php endif; ?>
</section>
