<?php
// Configuração do Banco de Dados
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'gerenciamento';

// Desabilitar a exibição de erros para evitar que HTML ou erros apareçam
ini_set('display_errors', 0);  // Desabilitar exibição de erros
error_reporting(E_ALL);        // Reportar todos os erros

$conn = new mysqli($host, $user, $password, $database);

// Verifica Conexão
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// Define o tipo de resposta como JSON
header('Content-Type: application/json');

// Função para verificar se o usuário é admin
function verificarAdmin($id_usuario, $conn) {
    $stmt = $conn->prepare("SELECT tipo FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario = $result->fetch_assoc();

    return $usuario && $usuario['tipo'] === 'admin';
}

// Endpoints
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
    $action = $_GET['action'];

    switch ($action) {
        case 'cadastro':
            $nome = $_POST['nome'];
            $email = $_POST['email'];
            $senha = password_hash($_POST['senha'], PASSWORD_BCRYPT);

            $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, 'cliente')");
            $stmt->bind_param("sss", $nome, $email, $senha);

            if ($stmt->execute()) {
                echo json_encode(["success" => true, "message" => "Usuário cadastrado com sucesso!"]);
            } else {
                echo json_encode(["success" => false, "message" => "Erro ao cadastrar usuário."]);
            }

            $stmt->close();
            break;

            case 'login':
                $email = $_POST['email'];
                $senha = $_POST['senha'];
            
                $stmt = $conn->prepare("SELECT id, senha, tipo FROM usuarios WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
            
                if ($result->num_rows > 0) {
                    $user = $result->fetch_assoc();
                    if (password_verify($senha, $user['senha'])) {
                        echo json_encode([
                            "success" => true,
                            "message" => "Login bem-sucedido!",
                            "id" => $user['id'],
                            "tipo" => $user['tipo'] // Retorna o tipo diretamente (admin ou cliente)
                        ]);
                    } else {
                        echo json_encode(["success" => false, "message" => "Senha incorreta."]);
                    }
                } else {
                    echo json_encode(["success" => false, "message" => "Usuário não encontrado."]);
                }
            
                $stmt->close();
                break;
            
            

                case 'listar_usuarios':
                    // Verifica se a conexão com o banco está funcionando
                    if (!$conn) {
                        echo json_encode(["success" => false, "message" => "Falha na conexão com o banco de dados"]);
                        break;
                    }
                
                    $sql = "SELECT id, nome, email, tipo FROM usuarios";
                    $result = $conn->query($sql);
                
                    // Verificando se a consulta foi bem-sucedida
                    if ($result === false) {
                        echo json_encode(["success" => false, "message" => "Erro na consulta SQL: " . $conn->error]);
                        break;
                    }
                
                    $usuarios = [];
                    while ($usuario = $result->fetch_assoc()) {
                        $usuarios[] = $usuario;
                    }
                
                    // Retorna sempre uma resposta JSON válida
                    echo json_encode(["success" => true, "usuarios" => $usuarios]);
                    break;
                
                
                
                
                
            
            case 'excluir_usuario':
                $id = $_POST['id'];
            
                $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
                $stmt->bind_param("i", $id);
            
                if ($stmt->execute()) {
                    echo json_encode(["success" => true, "message" => "Usuário excluído com sucesso!"]);
                } else {
                    echo json_encode(["success" => false, "message" => "Erro ao excluir usuário."]);
                }
            
            $stmt->close();
            break;
            


            case 'editar_usuario':
                $id = $_POST['id']; // ID do usuário a ser editado
                $nome = $_POST['nome'];
                $email = $_POST['email'];
                $tipo = $_POST['tipo']; // Tipo pode ser 'admin' ou 'cliente'
    
                // Atualiza o usuário no banco de dados
                $stmt = $conn->prepare("UPDATE usuarios SET nome = ?, email = ?, tipo = ? WHERE id = ?");
                $stmt->bind_param("sssi", $nome, $email, $tipo, $id);
    
                if ($stmt->execute()) {
                    echo json_encode(["success" => true, "message" => "Usuário atualizado com sucesso!"]);
                } else {
                    echo json_encode(["success" => false, "message" => "Erro ao atualizar usuário."]);
                }
    
                $stmt->close();
                break;
    
                 
            //Pedido Inicio

            case 'criar_pedido':
                $id_usuario = $_POST['id_usuario'];
                $origem = $_POST['origem'];
                $destino = $_POST['destino'];
                $peso = $_POST['peso'];
                $dimensoes = $_POST['dimensoes'];
                $tipo = $_POST['tipo'];
                $preco_total = $_POST['preco_total'];
            
                // Inserir os dados na tabela 'pedidos', com o ID do usuário correto
                $sql = "INSERT INTO pedidos (id_usuario, origem, destino, peso, dimensoes, tipo, preco_total) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("issdssd", $id_usuario, $origem, $destino, $peso, $dimensoes, $tipo, $preco_total);
                $stmt->execute();
            
                // Retornar uma resposta
                echo json_encode(['success' => true, 'message' => 'Pedido criado com sucesso']);
                break;
            
                case 'listar_todos_pedidos':
                    if (!$conn) {
                        error_log("Falha na conexão com o banco de dados.");
                        echo json_encode(["success" => false, "message" => "Falha na conexão com o banco de dados"]);
                        break;
                    }
                
                    $sql = "SELECT id_pedido, origem, destino, preco_total FROM pedidos";
                    error_log("Consulta SQL (listar_todos_pedidos): " . $sql); // Log da consulta SQL
                
                    $result = $conn->query($sql);
                
                    if ($result === false) {
                        error_log("Erro na consulta SQL (listar_todos_pedidos): " . $conn->error);
                        echo json_encode(["success" => false, "message" => "Erro na consulta SQL: " . $conn->error]);
                        break;
                    }
                
                    $pedidos = [];
                    while ($pedido = $result->fetch_assoc()) {
                        $pedidos[] = $pedido;
                    }
                
                    // Loga os pedidos antes de enviar
                    error_log("Resultado da consulta (listar_todos_pedidos): " . print_r($pedidos, true));
                
                    // Garante que algo será enviado como resposta
                    if (empty($pedidos)) {
                        echo json_encode(["success" => false, "message" => "Nenhum pedido encontrado."]);
                    } else {
                        echo json_encode(["success" => true, "pedidos" => $pedidos]);
                    }
                    break;
                
                


        // Exemplo de como você pode retornar os pedidos no formato correto
// No backend (index.php), altere a função 'listar_pedidos' para buscar os pedidos do usuário logado
case 'listar_pedidos':
    $id_usuario = $_POST['id_usuario']; // Obter o ID do usuário da requisição

    // Verifique se o id_usuario está presente
    if (!$id_usuario) {
        echo json_encode([]); // Se não tiver id_usuario, retorne um array vazio
        break;
    }

    // Buscar apenas os pedidos do usuário específico
    $sql = "SELECT * FROM pedidos WHERE id_usuario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_usuario); // "i" para inteiro, que é o tipo do id_usuario
    $stmt->execute();
    $result = $stmt->get_result();

    // Recupera os pedidos
    $pedidos = [];
    while ($pedido = $result->fetch_assoc()) {
        $pedidos[] = $pedido;
    }

    // Retorna os pedidos em formato JSON
    echo json_encode($pedidos);
    break;





        case 'atualizar_pedido':
            $id = $_POST['id'];
            $status = $_POST['status'];

            $stmt = $conn->prepare("UPDATE pedidos SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $status, $id);

            if ($stmt->execute()) {
                echo json_encode(["success" => true, "message" => "Pedido atualizado com sucesso!"]);
            } else {
                echo json_encode(["success" => false, "message" => "Erro ao atualizar pedido."]);
            }

            $stmt->close();
            break;

            /*case 'criar_pedido':
                $id_usuario = $_POST['id_usuario'];
                $destino = $_POST['destino'];
                $peso = $_POST['peso'];
                $dimensoes = $_POST['dimensoes'];
                $tipo = $_POST['tipo'];
                $preco_total =  floatval($_POST['preco_total']); // Receber o custo total do frontend
            
                // Adicionar um var_dump para verificar o valor recebido
                var_dump($_POST['preco_total']);
                exit; // Parar a execução para ver o valor recebido
            
                $stmt = $conn->prepare("INSERT INTO pedidos (id_usuario, destino, peso, dimensoes, tipo, preco_total) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isdssd", $id_usuario, $destino, $peso, $dimensoes, $tipo, $preco_total);
            
                if ($stmt->execute()) {
                    echo json_encode(["success" => true, "message" => "Pedido criado com sucesso!"]);
                } else {
                    echo json_encode(["success" => false, "message" => "Erro ao criar pedido."]);
                }
            
                $stmt->close();
                break;*/
            
            
            

        case 'excluir_pedido':
            $id = $_POST['id'];

            $stmt = $conn->prepare("DELETE FROM pedidos WHERE id = ?");
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                echo json_encode(["success" => true, "message" => "Pedido excluído com sucesso!"]);
            } else {
                echo json_encode(["success" => false, "message" => "Erro ao excluir pedido."]);
            }

            $stmt->close();
            break;

        default:
            echo json_encode(["success" => false, "message" => "Ação inválida."]);
            break;
    }
}
?>
