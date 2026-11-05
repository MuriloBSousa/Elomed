<?php
// Nao precisa de require: o autoload (app/config/autoload.php) ja
// carrega a classe Session. Este arquivo so define a funcao.
/**
 * flash_toasts
 * Gera o <script> que chama showToast() para cada mensagem de sessao.
 *
 * Este e o UNICO lugar do projeto que converte mensagem de servidor
 * em toast. Chame-o uma vez por requisicao, DEPOIS do toasts.js.
 *
 * Por que as mensagens entram com atraso (300ms entre uma e outra):
 * quando duas mensagens sao disparadas no mesmo instante, a segunda
 * apagaria a primeira. O toasts.js antigo removia o toast anterior a
 * cada chamada; o novo empilha (capitulo 1.4).
 */
function flash_toasts(): void {
    Session::start();

    $fila = Session::toastsFlash();
    if (!$fila) {
        return;
    }

    Session::limparFlash();

    // JSON_HEX_TAG/AMP/APOS/QUOT impedem que uma mensagem contendo
    // "</script>" feche o bloco e quebre a pagina. As mensagens do
    // projeto vem de $e->getMessage() e de erros de validacao, que
    // podem conter texto digitado pelo usuario.
    $json = json_encode(
        $fila,
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP
        | JSON_HEX_APOS | JSON_HEX_QUOT
    );

    echo '<script>(function(){'
       . 'var f=' . $json . ';'
       . 'function proximo(){'
       . '  if(typeof showToast!=="function"){return;}'
       . '  if(!f.length){return;}'
       . '  var m=f.shift();'
       . '  showToast(m.tipo,m.mensagem,3000);'
       . '  setTimeout(proximo,300);'
       . '}'
       . 'if(document.readyState==="loading"){'
       . '  document.addEventListener("DOMContentLoaded",proximo);'
       . '}else{proximo();}'
       . '})();</script>';
}
