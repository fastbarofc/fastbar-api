<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bem-vindo à Parthenon</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
            line-height: 1.6;
        }
        
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .header {
            background: linear-gradient(135deg, #aa8534 0%, #c9a556 100%);
            padding: 40px 30px;
            text-align: center;
        }
        
        .logo {
            font-size: 32px;
            font-weight: bold;
            color: #ffffff;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        
        .content {
            padding: 40px 30px;
        }
        
        .greeting {
            font-size: 24px;
            color: #333333;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .message {
            color: #555555;
            font-size: 16px;
            margin-bottom: 30px;
            line-height: 1.8;
        }
        
        .credentials-box {
            background-color: #f9f9f9;
            border-left: 4px solid #aa8534;
            padding: 25px;
            margin: 30px 0;
            border-radius: 4px;
        }
        
        .credentials-box h3 {
            color: #aa8534;
            font-size: 18px;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .credential-item {
            margin: 12px 0;
            display: flex;
            align-items: center;
        }
        
        .credential-label {
            font-weight: 600;
            color: #333333;
            min-width: 80px;
            font-size: 15px;
        }
        
        .credential-value {
            color: #555555;
            font-family: 'Courier New', monospace;
            background-color: #ffffff;
            padding: 8px 12px;
            border-radius: 4px;
            border: 1px solid #e0e0e0;
            font-size: 14px;
        }
        
        .warning-box {
            background-color: #fff9e6;
            border: 1px solid #ffd966;
            padding: 15px;
            border-radius: 4px;
            margin: 25px 0;
        }
        
        .warning-box p {
            color: #856404;
            font-size: 14px;
            margin: 0;
        }
        
        .cta-button {
            display: inline-block;
            background-color: #aa8534;
            color: #ffffff;
            padding: 14px 35px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            font-size: 16px;
            margin: 20px 0;
            transition: background-color 0.3s ease;
        }
        
        .cta-button:hover {
            background-color: #8f6d2a;
        }
        
        .footer {
            background-color: #2c2c2c;
            color: #ffffff;
            padding: 30px;
            text-align: center;
            font-size: 14px;
        }
        
        .footer p {
            margin: 8px 0;
            color: #cccccc;
        }
        
        .footer-brand {
            color: #aa8534;
            font-weight: 600;
            font-size: 18px;
            margin-bottom: 10px;
        }
        
        .divider {
            height: 1px;
            background-color: #e0e0e0;
            margin: 25px 0;
        }
        
        @media only screen and (max-width: 600px) {
            .email-container {
                border-radius: 0;
            }
            
            .content {
                padding: 25px 20px;
            }
            
            .header {
                padding: 30px 20px;
            }
            
            .credential-item {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .credential-label {
                margin-bottom: 5px;
            }
            
            .credential-value {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="logo">PARTHENON</div>
        </div>
        
        <div class="content">
            <h1 class="greeting">Olá, {{ $user->name }}!</h1>
            
            <p class="message">
                É um prazer recebê-lo(a) na equipe Parthenon! Seu acesso ao sistema foi criado com sucesso e você já pode começar a utilizar todas as funcionalidades disponíveis.
            </p>
            
            <div class="credentials-box">
                <h3>Seus Dados de Acesso</h3>
                <div class="credential-item">
                    <span class="credential-label">Login:</span>
                    <span class="credential-value">{{ $user->email }}</span>
                </div>
                <div class="credential-item">
                    <span class="credential-label">Senha:</span>
                    <span class="credential-value">{{ $password }}</span>
                </div>
            </div>
            
            <div class="warning-box">
                <p>
                    ⚠️ <strong>Importante:</strong> Por questões de segurança, recomendamos que você altere sua senha no primeiro acesso ao sistema.
                </p>
            </div>
            
            <div class="divider"></div>
            
            <p class="message">
                Se você tiver qualquer dúvida ou precisar de ajuda, não hesite em entrar em contato com nossa equipe de suporte.
            </p>
            
            <p class="message" style="margin-top: 30px; color: #888888; font-size: 14px;">
                Atenciosamente,<br>
                <strong style="color: #aa8534;">Equipe Parthenon</strong>
            </p>
        </div>
        
        <div class="footer">
            <div class="footer-brand">PARTHENON</div>
            <p>Este é um e-mail automático, por favor não responda.</p>
            <p style="margin-top: 15px; font-size: 12px;">
                © {{ date('Y') }} Parthenon. Todos os direitos reservados.
            </p>
        </div>
    </div>
</body>
</html>
