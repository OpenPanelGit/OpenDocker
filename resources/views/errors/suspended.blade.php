<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Suspended | OpenDocker</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #0d0d0d;
            background-image: 
                radial-gradient(at 0% 0%, hsla(253,16%,7%,1) 0, transparent 50%), 
                radial-gradient(at 50% 0%, hsla(225,39%,30%,1) 0, transparent 50%), 
                radial-gradient(at 100% 0%, hsla(339,49%,30%,1) 0, transparent 50%);
            color: #ffffff;
            height: 100vh;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .glass {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
        }
        .glow-text {
            text-shadow: 0 0 20px rgba(255, 77, 77, 0.5);
        }
    </style>
</head>
<body>
    <div class="glass p-12 rounded-2xl max-w-md w-full text-center relative overflow-hidden">
        <!-- Decoration -->
        <div class="absolute -top-10 -right-10 w-32 h-32 bg-red-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-pulse"></div>
        <div class="absolute -bottom-10 -left-10 w-32 h-32 bg-purple-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-pulse"></div>

        <!-- Verify Icon -->
        <div class="mb-6 mx-auto w-20 h-20 bg-red-500/10 rounded-full flex items-center justify-center border border-red-500/20">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>

        <h1 class="text-3xl font-bold mb-2 glow-text tracking-tight">Compte Suspendu</h1>
        <p class="text-gray-400 mb-8 leading-relaxed">
            L'accès à votre compte a été temporairement restreint par un administrateur. 
            Veuillez contacter le support pour résoudre ce problème.
        </p>

        <div class="space-y-4">
            <a href="mailto:support@opendocker.com" class="block w-full py-3 px-6 bg-white text-black font-bold rounded-lg hover:bg-gray-200 transition-all transform hover:scale-[1.02] shadow-lg shadow-white/10">
                Contacter le Support
            </a>
            
            <form action="{{ route('auth.logout') }}" method="POST">
                @csrf
                <button type="submit" class="block w-full py-3 px-6 bg-transparent border border-white/10 text-white/70 font-semibold rounded-lg hover:bg-white/5 hover:text-white transition-colors">
                    Se déconnecter
                </button>
            </form>
        </div>
        
        <div class="mt-8 text-xs text-gray-600 font-mono">
            ID: {{ Auth::user()->uuidShort }}
        </div>
    </div>
</body>
</html>
