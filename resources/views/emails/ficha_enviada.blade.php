<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscrição Enviada</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4;">
    <div style="max-width: 600px; margin: 40px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background-color: #ffffff; box-shadow: 0 4px 8px rgba(0,0,0,0.05);">
        
        <div style="text-align: center; padding-bottom: 20px;">
            <h1 style="color: #004d40; margin: 0;">{{ $nomeMovimento }}</h1>
            <p style="color: #00796b;">{{ $paroquia }}</p>
        </div>

        <h2 style="color: #0277bd;">Inscrição Registrada!</h2>

        <p>Olá,</p>

        <p>Gostaríamos de dar ciência de que o(a) jovem <strong>{{ $candidato }}</strong> está participando do evento <strong>{{ $evento }}</strong> ({{ $siglaMovimento }}), que ocorrerá entre os dias <strong>{{ $dataInicio }}</strong> e <strong>{{ $dataTermino }}</strong>.</p>
        
        <div style="background-color: #e3f2fd; padding: 15px; border-left: 4px solid #0288d1; margin: 20px 0;">
            <strong>Autorização de Imagem e Ciência:</strong><br>
            Ao clicar no botão abaixo, você confirma a ciência da participação e nos autoriza a utilizar a imagem do(a) jovem em nossas publicações e vídeos relacionados ao movimento e à paróquia.
        </div>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $urlAutorizacao }}" style="background-color: #0277bd; color: #ffffff; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;">
                Ciente e Autorizo
            </a>
        </div>
        
        <p>Em caso de dúvidas ou para mais informações, não hesite em nos contatar pelo nosso site: <a href="https://movimento.canonico.com.br" style="color: #0288d1;">movimento.canonico.com.br</a>.</p>

        <p style="margin-top: 30px;">
            A paz de Cristo,<br>
            <strong>{{ $paroquia }}</strong>
        </p>
    </div>
</body>
</html>
