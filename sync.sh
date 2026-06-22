#!/bin/bash

# Leer la opción que escribe el usuario
OPCION=$1

# Omitir confirmación
OMITIR_CONFIRMACION=false
for arg in "$@"; do
    if [ "$arg" == "--yes" ] || [ "$arg" == "-y" ]; then
        OMITIR_CONFIRMACION=true
    fi
done

# Nombre de tu rama principal
RAMA="main"

# Definir colores para la terminal
ROJO='\033[0;31m'
VERDE='\033[0;32m'
AMARILLO='\033[1;33m'
AZUL='\033[0;34m'
SIN_COLOR='\033[0;0m' # Borra el color para volver al texto normal

# Función para pedir confirmación en base a la acción
confirmar_accion() {
    local mensaje_accion=$1
    echo -e "${ROJO}[!] ¡ATENCIÓN PELIGRO!${SIN_COLOR}"
    echo -e "Estás a punto de: ${AMARILLO}$mensaje_accion${SIN_COLOR}"
    
    # Preguntar al usuario convirtiendo la respuesta a minúsculas
    read -p "¿Estás seguro de que deseas continuar? (escribe 'si' para confirmar): " respuesta
    respuesta_minuscula=$(echo "$respuesta" | tr '[:upper:]' '[:lower:]')
    
    if [ "$respuesta_minuscula" == "si" ]; then
        return 0 # Continuar
    else
        echo -e "${ROJO}[X] Acción cancelada por el usuario.${SIN_COLOR}\n"
        return 1 # Cancelar
    fi
}

# CASO 1: Ignorar lo local y descargar lo remoto
if [ "$OPCION" == "--ignore-local" ]; then
    echo -e "${AZUL}--- Opción seleccionada: Ignorar cambios locales ---${SIN_COLOR}"
    
    if confirmar_accion "BORRAR todo tu trabajo local no guardado para dejar tu PC igual a GitHub"; then
        echo -e "${AMARILLO}Limpiando archivos locales y descargando desde GitHub...${SIN_COLOR}"
        git fetch origin
        git reset --hard origin/$RAMA
        echo -e "${VERDE}¡Hecho! Tu equipo local ahora está exactamente igual que GitHub.${SIN_COLOR}"
    fi
    exit 0

# CASO 2: Subir lo local e ignorar lo remoto (Fuerza la subida)
elif [ "$OPCION" == "--ignore-remote" ]; then
    echo -e "${AZUL}--- Opción seleccionada: Forzar subida local ---${SIN_COLOR}"
    
    if confirmar_accion "SOBRESCRIBIR GitHub con tus archivos actuales (se perderán cambios de otros equipos)"; then
        echo -e "${AMARILLO}Guardando cambios locales...${SIN_COLOR}"
        git add .
        read -p "Introduce el mensaje para tu commit: " mensaje
        git commit -m "$mensaje"
        
        echo -e "${AMARILLO}Subiendo de forma forzada a GitHub...${SIN_COLOR}"
        git push origin $RAMA --force
        echo -e "${VERDE}¡Hecho! GitHub ahora tiene exactamente lo que hay en tu equipo.${SIN_COLOR}"
    fi
    exit 0

# CASO 3: Sincronización normal (si no pones ninguna opción)
else
    echo -e "${AZUL}--- Iniciando Sincronización Normal ---${SIN_COLOR}"
    git status
    
    # --- CONFIRMACIÓN Y EJECUCIÓN DEL PULL ---
    if [ "$OMITIR_CONFIRMACION" = false ]; then
        echo -e "\n${AMARILLO}[!]  ¿Deseas descargar los cambios desde GitHub? (git pull)${SIN_COLOR}"
        read -p "[s/N]: " resp_pull
        resp_pull=${resp_pull,,}
        if [ "$resp_pull" != "s" ]; then
            echo -e "${ROJO}[X] Sincronización cancelada. Proceso detenido.${SIN_COLOR}"
            exit 1
        fi
    fi

    echo -e "\n${AMARILLO}Descargando cambios desde GitHub...${SIN_COLOR}"
    git pull origin $RAMA
    
    # --- PROCESO INTERMEDIO (COMMIT) ---
    echo -e "\n${AMARILLO}Guardando cambios locales...${SIN_COLOR}"
    git add .
    read -p "Introduce el mensaje para tu commit: " mensaje
    git commit -m "$mensaje"

    # --- CONFIRMACIÓN Y EJECUCIÓN DEL PUSH ---
    if [ "$OMITIR_CONFIRMACION" = false ]; then
        echo -e "\n${AMARILLO}[!]  ¿Deseas subir tus cambios a GitHub? (git push)${SIN_COLOR}"
        read -p "[s/N]: " resp_push
        resp_push=${resp_push,,}
        if [ "$resp_push" != "s" ]; then
            echo -e "${ROJO}[X] Envío cancelado. Proceso detenido antes del push.${SIN_COLOR}"
            exit 1
        fi
    fi

    echo -e "\n${AMARILLO}Subiendo tus cambios locales...${SIN_COLOR}"
    git push origin $RAMA
    
    echo -e "${VERDE}\n¡Sincronización normal completada con éxito!${SIN_COLOR}"
    exit 0
fi
