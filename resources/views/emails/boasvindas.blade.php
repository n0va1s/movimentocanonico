<!DOCTYPE html>

<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Um prazer ter vc por aqui</title>
</head>

<body
    style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4;">

    <div
        style="max-width: 600px; margin: 40px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background-color: #ffffff; box-shadow: 0 4px 8px rgba(0,0,0,0.05);">

        <div style="text-align: center; padding-bottom: 20px;">
            <h1 style="color: #004d40; margin: 0;">Paróquia Nossa Senhora do Lago</h1>
            <p style="color: #00796b;">NTM - Núcleo de Transformação Missionária</p>
        </div>

        <h2 style="color: #4CAF50;">Boas-vindas, {{ $nome }}!</h2>

        <p>Seu acesso à plataforma do NTM foi criado com sucesso. Você pode fazer login utilizando os seguintes dados:
        </p>

        <table style="width: 100%; border-collapse: collapse; margin: 20px 0; border: 1px solid #e0e0e0;">
            <tr>
                <td
                    style="padding: 12px; background-color: #f9f9f9; width: 30%; font-weight: bold; border: 1px solid #e0e0e0;">
                    E-mail:</td>
                <td style="padding: 12px; background-color: #ffffff; width: 70%; border: 1px solid #e0e0e0;">
                    {{ $email }}</td>
            </tr>
            @if(!empty($senha))
            <tr>
                <td style="padding: 12px; background-color: #f9f9f9; font-weight: bold; border: 1px solid #e0e0e0;">
                    Senha Temporária:</td>
                <td style="padding: 12px; background-color: #ffffff; border: 1px solid #e0e0e0;">
                    <strong style="color: #c0392b; letter-spacing: 1px;">{{ $senha }}</strong>
                </td>
            </tr>
            @endif
        </table>

        @if(!empty($senha))
        <p
            style="font-size: 0.9em; color: #e67e22; padding: 10px; background-color: #fffbe6; border-left: 3px solid #e67e22;">
            <strong>Lembrete Importante:</strong> Sua senha inicial é a sua **data de nascimento** no formato
            **AAAAMMDD** (por exemplo, 19850520). Por favor, altere esta senha após seu primeiro acesso para garantir
            sua segurança.
        </p>
        @endif

        <p style="margin-top: 20px; font-size: 1.05em; color: #444;">
            O <strong>Movimento Canônico</strong> é a principal forma para você participar dos movimentos paroquiais como VEM, Segue-Me e ECC.
            É muito importante manter seus dados atualizados para serem usados nas montagens.
        </p>

        <p style="font-size: 1.05em; color: #444;">
            Além disso, lembramos que é fundamental participar do <strong>Aura</strong>! Você ganhará pontos em cada evento que participar, crescendo junto com nossa comunidade.
        </p>

        <div style="text-align: center; margin-top: 30px;">
            <a href="{{ url('/login') }}"
                style="display: inline-block; padding: 12px 25px; background-color: #00796b; color: #ffffff; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 16px;">
                Acessar a Plataforma
            </a>
        </div>

        <p style="margin-top: 30px;">
            Seja muito bem-vindo(a) à nossa missão. Qualquer dúvida sobre o acesso, entre em contato.
        </p>
        <p>
            Atenciosamente,<br>
            A Equipe da Paróquia Nossa Senhora do Lago
        </p>
    </div>

</body>

</html>
