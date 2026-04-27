<?php

namespace App\Services;

use App\Models\Order;
use App\Models\StoreSetting;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * ReceiptService — [PUNTO-5] Genera recibos PDF de compra.
 *
 * Requiere dompdf:
 *   composer require barryvdh/laravel-dompdf
 *
 * Uso:
 *   $pdf  = app(ReceiptService::class)->generate($order);           // Pdf object
 *   $path = app(ReceiptService::class)->saveToTmp($order);          // path en disco
 *   $data = app(ReceiptService::class)->content($order);            // string content
 */
class ReceiptService
{
    /**
     * Genera el PDF y devuelve el objeto Pdf (dompdf).
     */
    public function generate(Order $order): \Barryvdh\DomPDF\PDF
    {
        $order->loadMissing(['buyer', 'items']);

        $data = [
            'order'       => $order,
            'storeName'   => StoreSetting::get('store_name',  'Nexo eStore'),
            'storeTagline'=> StoreSetting::get('store_tagline','Tu tienda de claves digitales'),
            'supportEmail'=> StoreSetting::get('support_email','soporte@nexo.store'),
            'storeUrl'    => rtrim(config('app.url'), '/'),
        ];

        return Pdf::loadView('pdf.receipt', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'dpi'                   => 150,
                'isHtml5ParserEnabled'  => true,
                'isRemoteEnabled'       => false,  // no cargar recursos externos (seguridad)
                'defaultFont'           => 'DejaVu Sans',
                'isFontSubsettingEnabled' => true,
            ]);
    }

    /**
     * Genera y guarda el PDF en /tmp. Devuelve la ruta del archivo.
     * Útil para adjuntarlo como Attachment en notificaciones.
     */
    public function saveToTmp(Order $order): string
    {
        $shortId = strtoupper(substr($order->ulid, -8));
        $path    = sys_get_temp_dir()."/nexo_receipt_{$shortId}.pdf";

        $this->generate($order)->save($path);

        return $path;
    }

    /**
     * Devuelve el contenido binario del PDF (para adjuntar inline).
     */
    public function content(Order $order): string
    {
        return $this->generate($order)->output();
    }
}
