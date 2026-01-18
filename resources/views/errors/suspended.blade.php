@extends('templates/wrapper', [
    'css' => ['body' => 'bg-zinc-900 font-jakarta h-screen flex items-center justify-center']
])

@section('container')
    <div class="relative w-full max-w-md p-6">
        <!-- Background Effects -->
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-64 h-64 bg-brand/20 rounded-full blur-[100px] pointer-events-none"></div>

        <div class="relative bg-zinc-800/50 border border-white/5 backdrop-blur-xl rounded-2xl p-8 shadow-2xl text-center">
            <!-- Icon -->
            <div class="mx-auto w-16 h-16 bg-red-500/10 rounded-full flex items-center justify-center mb-6 border border-red-500/20">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>

            <!-- Title -->
            <h1 class="text-2xl font-bold text-white mb-3">Compte Suspendu</h1>
            
            <!-- Description -->
            <p class="text-zinc-400 mb-8 leading-relaxed text-sm">
                L'accès à votre compte a été temporairement restreint par un administrateur. 
                Veuillez contacter le support pour résoudre ce problème.
            </p>

            <!-- Actions -->
            <div class="space-y-3">
                <a href="mailto:support@openpanel.dev" class="flex items-center justify-center w-full py-2.5 px-4 bg-white text-zinc-900 font-semibold rounded-lg hover:bg-zinc-100 transition-colors">
                    Contacter le Support
                </a>
                
                <form action="{{ route('auth.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full py-2.5 px-4 bg-transparent border border-zinc-700 text-zinc-400 font-medium rounded-lg hover:bg-zinc-800 hover:text-white transition-colors">
                        Se déconnecter
                    </button>
                </form>
            </div>
            
            <!-- User ID -->
            <div class="mt-8 pt-6 border-t border-white/5">
                <p class="text-xs text-zinc-600 font-mono">
                    ID: {{ Auth::user()->uuidShort }}
                </p>
            </div>
        </div>
        
        <!-- Branding Footer -->
        <div class="mt-8 text-center">
            <p class="text-zinc-600 text-xs">
                &copy; {{ date('Y') }} OpenPanel
            </p>
        </div>
    </div>
@endsection
