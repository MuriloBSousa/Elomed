<?php
    // app/controllers/ErroController.php
    class ErroController {
        public static function erro404() {
            http_response_code(404);
            echo '<!DOCTYPE html>';
            echo '<html lang="pt-br"><head><meta charset="UTF-8">';
            echo '<title>Erro 404</title></head><body>';
            echo '<h1>Erro 404</h1>';
            echo '<p>Pagina nao encontrada. <a href="' . BASE_URL . '/login/index">Voltar ao inicio</a></p>';
            echo '</body></html>';
        }
    }