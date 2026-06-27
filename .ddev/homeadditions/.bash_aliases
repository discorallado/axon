# #ddev-generated
# To make this file active you can either
# cp bash_aliases.example .bash_aliases
# or ln -s bash_aliases.example .bash_aliases

alias ll="ls -lhA"

function pa(){
    php artisan "$@"
}
# 1. Nueva función pao que acepta subcomandos dinámicos
function pao() {
    if [[ "$1" == :* ]]; then
        # Si el primer argumento empieza con ":", removemos los dos puntos
        # Ejemplo: :clear se convierte en clear, y se une a php artisan optimize:clear
        local subcomando="${1#:}"
        shift
        php artisan optimize:"$subcomando" "$@"
    else
        # Si no lleva dos puntos, ejecuta el comando base original
        php artisan optimize "$@"
    fi
}

