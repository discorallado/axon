<button
    type="submit"
    wire:loading.attr="disabled"
    wire:confirm="¿Confirmar el envío de la solicitud? Una vez enviada, se notificará al equipo técnico."
    class="pf-btn-primary mt-2"
>
    <span wire:loading wire:target="submit" class="pf-spinner mr-2"></span>
    <span wire:loading.remove wire:target="submit">Enviar solicitud</span>
    <span wire:loading wire:target="submit">Enviando…</span>
</button>
