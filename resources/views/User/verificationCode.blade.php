<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuario Verificado</title>
    <link rel="stylesheet" href="styles.css">
    <icon rel="icon" href="https://085cb14e85.imgdist.com/pub/bfra/tdqbpcts/ofs/m81/9sq/medicarelogo.png"
        type="image/x-icon">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    .ContenedorBase {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        width: 100vw;
        font-family: Arial, sans-serif;
        font-size: 24px;
        color: #333;
        background: linear-gradient(to left, #081655 0%, #000000 100%);
        flex-direction: column;
    }

    p {
        margin-top: 5%;
        text-align: center;
        color: #3e79fa;
        font-size: 2rem;
    }

    .ImgMedicare {
        max-width: 50vh;
        max-height: 50vh;
        width: 100%;
        height: auto;
        margin-bottom: 2rem;
    }

    @media (max-width: 900px) {
        .ContenedorBase {
            font-size: 18px;
            padding: 2rem;
        }

        .ImgMedicare {
            max-width: 40vh;
            max-height: 40vh;
        }

        p {
            font-size: 2rem;
        }
    }

    @media (max-width: 600px) {
        .ContenedorBase {
            font-size: 16px;
            padding: 1rem;
        }

        .ImgMedicare {
            max-width: 35vh;
            max-height: 35vh;
        }

        p {
            font-size: 1.3rem;
            margin-top: 2rem;
        }
    }
</style>

<body>
    <div class="ContenedorBase">
        <img src="https://085cb14e85.imgdist.com/pub/bfra/tdqbpcts/ofs/m81/9sq/medicarelogo.png" alt="medicarelogo"
            class="ImgMedicare">
        <p>{{$message}}</p>
    </div>
</body>

</html>