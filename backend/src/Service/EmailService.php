<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private string $fromEmail = 'noreply@al3dprint.fr',
        private string $adminEmail = 'admin@al3dprint.fr',
        private string $appUrl = 'http://localhost:5173'
    ) {
    }

    public function sendPasswordResetEmail(string $toEmail, string $resetToken, string $userName): void
    {
        $resetUrl = rtrim($this->appUrl, '/') . '/reset-password?token=' . $resetToken;

        // Template HTML simple (sans Twig pour l'instant)
        $htmlContent = $this->getPasswordResetEmailTemplate($userName, $resetUrl, $resetToken);

        $email = (new Email())
            ->from($this->fromEmail)
            ->to($toEmail)
            ->subject('Réinitialisation de votre mot de passe - Al3DPrint')
            ->html($htmlContent);

        $this->mailer->send($email);
    }

    // ─── Email de confirmation commande → client ───────────────────────────────

    public function sendOrderConfirmationEmail(array $orderData, string $toEmail): void
    {
        $email = (new Email())
            ->from($this->fromEmail)
            ->to($toEmail)
            ->subject('Confirmation de votre commande ' . ($orderData['numero_commande'] ?? '#' . $orderData['id']) . ' - Al3DPrint')
            ->html($this->getOrderConfirmationTemplate($orderData));

        $this->mailer->send($email);
    }

    // ─── Email de notification nouvelle commande → admin ──────────────────────

    public function sendNewOrderAdminEmail(array $orderData): void
    {
        $email = (new Email())
            ->from($this->fromEmail)
            ->to($this->adminEmail)
            ->subject('🛒 Nouvelle commande ' . ($orderData['numero_commande'] ?? '#' . $orderData['id']) . ' - Al3DPrint')
            ->html($this->getAdminNewOrderTemplate($orderData));

        $this->mailer->send($email);
    }

    // ─── Email de bienvenue → client à l'inscription ──────────────────────────

    public function sendWelcomeEmail(string $toEmail, string $userName): void
    {
        $email = (new Email())
            ->from($this->fromEmail)
            ->to($toEmail)
            ->subject('Bienvenue sur Al3DPrint, ' . $userName . ' !')
            ->html($this->getWelcomeTemplate($userName));

        $this->mailer->send($email);
    }

    // ─── Ancien alias conservé pour compatibilité ─────────────────────────────

    public function sendOrderEmail(array $orderData, string $toEmail): void
    {
        $htmlContent = $this->getOrderEmailTemplate($orderData);

        $email = (new Email())
            ->from($this->fromEmail)
            ->to($toEmail)
            ->subject('Détails de votre commande #' . $orderData['id'] . ' - Al3DPrint')
            ->html($htmlContent);

        $this->mailer->send($email);
    }

    public function sendStatusUpdateEmail(array $orderData, string $toEmail): void
    {
        $htmlContent = $this->getStatusUpdateEmailTemplate($orderData);

        $email = (new Email())
            ->from($this->fromEmail)
            ->to($toEmail)
            ->subject('Mise à jour de votre commande #' . $orderData['id'] . ' - Al3DPrint')
            ->html($htmlContent);

        $this->mailer->send($email);
    }

    public function getOrderEmailPreview(array $orderData): string
    {
        return $this->getOrderEmailTemplate($orderData);
    }

    private function getOrderEmailTemplate(array $orderData): string
    {
        $orderId = htmlspecialchars($orderData['id'] ?? '', ENT_QUOTES, 'UTF-8');
        $clientName = htmlspecialchars($orderData['client']['nom'] ?? 'Client', ENT_QUOTES, 'UTF-8');
        $orderDate = htmlspecialchars($orderData['date_commande'] ?? '', ENT_QUOTES, 'UTF-8');
        $status = htmlspecialchars($orderData['statut'] ?? '', ENT_QUOTES, 'UTF-8');
        $total = htmlspecialchars(number_format($orderData['total'] ?? 0, 2, ',', ' ') . '€', ENT_QUOTES, 'UTF-8');
        $deliveryAddress = nl2br(htmlspecialchars($orderData['adresse_livraison'] ?? 'Non spécifiée', ENT_QUOTES, 'UTF-8'));
        $trackingNumber = htmlspecialchars($orderData['numero_suivi'] ?? 'Non disponible', ENT_QUOTES, 'UTF-8');
        $paymentMethod = htmlspecialchars($orderData['mode_paiement'] ?? 'Non spécifié', ENT_QUOTES, 'UTF-8');
        $currentYear = date("Y");

        // Générer le tableau des articles
        $itemsHtml = '';
        if (isset($orderData['details']) && is_array($orderData['details'])) {
            foreach ($orderData['details'] as $item) {
                $itemName = htmlspecialchars($item['produit']['nom'] ?? 'Produit', ENT_QUOTES, 'UTF-8');
                $itemQty = htmlspecialchars($item['quantite'] ?? 0, ENT_QUOTES, 'UTF-8');
                $itemPrice = htmlspecialchars(number_format($item['prix_unitaire'] ?? 0, 2, ',', ' ') . '€', ENT_QUOTES, 'UTF-8');
                $itemTotal = htmlspecialchars(number_format(($item['prix_unitaire'] ?? 0) * ($item['quantite'] ?? 0), 2, ',', ' ') . '€', ENT_QUOTES, 'UTF-8');
                
                $itemsHtml .= <<<HTML
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #e0e0e0;">{$itemName}</td>
                    <td style="padding: 10px; border-bottom: 1px solid #e0e0e0; text-align: center;">{$itemQty}</td>
                    <td style="padding: 10px; border-bottom: 1px solid #e0e0e0; text-align: right;">{$itemPrice}</td>
                    <td style="padding: 10px; border-bottom: 1px solid #e0e0e0; text-align: right; font-weight: bold;">{$itemTotal}</td>
                </tr>
HTML;
            }
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails de votre commande</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .email-container {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #00d4ff;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #00d4ff;
            margin-bottom: 10px;
        }
        .content {
            margin-bottom: 30px;
        }
        .info-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: bold;
            color: #666;
        }
        .info-value {
            color: #333;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .items-table th {
            background-color: #00d4ff;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: bold;
        }
        .items-table td {
            padding: 10px;
            border-bottom: 1px solid #e0e0e0;
        }
        .total-section {
            background-color: #00d4ff;
            color: white;
            padding: 15px;
            border-radius: 8px;
            text-align: right;
            font-size: 20px;
            font-weight: bold;
            margin-top: 20px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 12px;
            background-color: #00d4ff;
            color: white;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="logo">Al3DPrint</div>
            <h1>Détails de votre commande</h1>
        </div>

        <div class="content">
            <p>Bonjour {$clientName},</p>
            
            <p>Voici les détails de votre commande :</p>

            <div class="info-section">
                <div class="info-row">
                    <span class="info-label">Numéro de commande :</span>
                    <span class="info-value">#{$orderId}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date de commande :</span>
                    <span class="info-value">{$orderDate}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Statut :</span>
                    <span class="info-value"><span class="status-badge">{$status}</span></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Mode de paiement :</span>
                    <span class="info-value">{$paymentMethod}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Numéro de suivi :</span>
                    <span class="info-value">{$trackingNumber}</span>
                </div>
            </div>

            <h3 style="margin-top: 30px; color: #00d4ff;">Adresse de livraison</h3>
            <div class="info-section">
                <p style="margin: 0; white-space: pre-line;">{$deliveryAddress}</p>
            </div>

            <h3 style="margin-top: 30px; color: #00d4ff;">Articles commandés</h3>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Article</th>
                        <th style="text-align: center;">Quantité</th>
                        <th style="text-align: right;">Prix unitaire</th>
                        <th style="text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    {$itemsHtml}
                </tbody>
            </table>

            <div class="total-section">
                Total : {$total}
            </div>
        </div>

        <div class="footer">
            <p>Merci pour votre confiance !</p>
            <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
            <p>&copy; {$currentYear} Al3DPrint. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    private function getStatusUpdateEmailTemplate(array $orderData): string
    {
        $orderId        = htmlspecialchars((string) ($orderData['id'] ?? ''), ENT_QUOTES, 'UTF-8');
        $clientName     = htmlspecialchars($orderData['client']['nom'] ?? 'Client', ENT_QUOTES, 'UTF-8');
        $statut         = htmlspecialchars($orderData['statut'] ?? '', ENT_QUOTES, 'UTF-8');
        $trackingNumber = htmlspecialchars($orderData['numero_suivi'] ?? '', ENT_QUOTES, 'UTF-8');
        $currentYear    = date('Y');

        $statusColors = [
            'En attente'     => ['bg' => '#f59e0b', 'text' => '#ffffff'],
            'Payée'          => ['bg' => '#3b82f6', 'text' => '#ffffff'],
            'En cours'       => ['bg' => '#3b82f6', 'text' => '#ffffff'],
            'Expédiée'       => ['bg' => '#10b981', 'text' => '#ffffff'],
            'Livrée'         => ['bg' => '#059669', 'text' => '#ffffff'],
            'Annulée'        => ['bg' => '#ef4444', 'text' => '#ffffff'],
        ];
        $color   = $statusColors[$orderData['statut'] ?? ''] ?? ['bg' => '#00d4ff', 'text' => '#ffffff'];
        $bgColor = $color['bg'];

        $trackingBlock = '';
        if ($trackingNumber && in_array($orderData['statut'] ?? '', ['Expédiée', 'Livrée'], true)) {
            $trackingBlock = <<<HTML
            <div style="margin-top:20px; padding:15px; background:#f0fdf4; border:1px solid #86efac; border-radius:8px;">
                <p style="margin:0; font-weight:bold; color:#166534;">Numéro de suivi</p>
                <p style="margin:6px 0 0; font-family:monospace; font-size:16px; color:#15803d;">{$trackingNumber}</p>
            </div>
HTML;
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mise à jour commande #{$orderId}</title>
</head>
<body style="font-family:Arial,sans-serif;background:#f4f4f4;margin:0;padding:20px;">
    <div style="max-width:600px;margin:0 auto;background:#ffffff;border-radius:10px;padding:30px;box-shadow:0 2px 10px rgba(0,0,0,0.1);">

        <div style="text-align:center;padding-bottom:20px;border-bottom:2px solid #00d4ff;margin-bottom:30px;">
            <div style="font-size:28px;font-weight:bold;color:#00d4ff;">Al3DPrint</div>
            <h1 style="color:#333;margin:10px 0 0;">Mise à jour de votre commande</h1>
        </div>

        <p style="color:#333;">Bonjour {$clientName},</p>
        <p style="color:#555;">Le statut de votre commande <strong>#{$orderId}</strong> vient d'être mis à jour :</p>

        <div style="text-align:center;margin:30px 0;">
            <span style="display:inline-block;padding:12px 30px;background:{$bgColor};color:#ffffff;border-radius:25px;font-size:18px;font-weight:bold;">
                {$statut}
            </span>
        </div>

        {$trackingBlock}

        <p style="margin-top:30px;color:#555;">Vous pouvez suivre l'évolution de votre commande depuis votre espace client.</p>

        <div style="margin-top:30px;padding-top:20px;border-top:1px solid #e0e0e0;text-align:center;font-size:12px;color:#999;">
            <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
            <p>&copy; {$currentYear} Al3DPrint. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    // ─── Template : confirmation commande client ───────────────────────────────

    private function getOrderConfirmationTemplate(array $orderData): string
    {
        $numeroCommande = htmlspecialchars($orderData['numero_commande'] ?? ('#' . ($orderData['id'] ?? '')), ENT_QUOTES, 'UTF-8');
        $clientName     = htmlspecialchars($orderData['client']['nom'] ?? 'Client', ENT_QUOTES, 'UTF-8');
        $orderDate      = htmlspecialchars($orderData['date_commande'] ?? '', ENT_QUOTES, 'UTF-8');
        $total          = htmlspecialchars(number_format((float)($orderData['total'] ?? 0), 2, ',', ' ') . ' €', ENT_QUOTES, 'UTF-8');
        $address        = nl2br(htmlspecialchars($orderData['adresse_livraison'] ?? 'Non renseignée', ENT_QUOTES, 'UTF-8'));
        $payment        = htmlspecialchars($orderData['mode_paiement'] ?? 'Non spécifié', ENT_QUOTES, 'UTF-8');
        $currentYear    = date('Y');

        $itemsHtml = '';
        foreach ($orderData['details'] ?? [] as $item) {
            $name     = htmlspecialchars($item['produit']['nom'] ?? 'Article', ENT_QUOTES, 'UTF-8');
            $qty      = (int)($item['quantite'] ?? 1);
            $unit     = number_format((float)($item['prix_unitaire'] ?? 0), 2, ',', ' ');
            $lineTotal = number_format((float)($item['prix_unitaire'] ?? 0) * $qty, 2, ',', ' ');
            $itemsHtml .= <<<ROW
            <tr>
              <td style="padding:12px 10px;border-bottom:1px solid #e8e8e8;color:#333;">{$name}</td>
              <td style="padding:12px 10px;border-bottom:1px solid #e8e8e8;text-align:center;color:#555;">{$qty}</td>
              <td style="padding:12px 10px;border-bottom:1px solid #e8e8e8;text-align:right;color:#555;">{$unit} €</td>
              <td style="padding:12px 10px;border-bottom:1px solid #e8e8e8;text-align:right;font-weight:700;color:#1a1a2e;">{$lineTotal} €</td>
            </tr>
ROW;
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Confirmation commande {$numeroCommande}</title></head>
<body style="margin:0;padding:0;background:#f0f2f5;font-family:Arial,Helvetica,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f2f5;padding:30px 0;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08);max-width:600px;width:100%;">

  <!-- Header -->
  <tr><td style="background:linear-gradient(135deg,#1a1a2e 0%,#16213e 100%);padding:36px 40px;text-align:center;">
    <div style="font-size:32px;font-weight:900;color:#00d4ff;letter-spacing:-1px;margin-bottom:4px;">Al3DPrint</div>
    <div style="color:rgba(255,255,255,0.6);font-size:13px;letter-spacing:2px;text-transform:uppercase;">Impression 3D Professionnelle</div>
  </td></tr>

  <!-- Merci banner -->
  <tr><td style="background:linear-gradient(90deg,#00d4ff,#4ecdc4);padding:18px 40px;text-align:center;">
    <span style="color:#fff;font-size:20px;font-weight:700;">✅ Commande confirmée — Merci {$clientName} !</span>
  </td></tr>

  <!-- Body -->
  <tr><td style="padding:36px 40px;">

    <p style="color:#333;font-size:15px;line-height:1.7;margin:0 0 20px;">
      Bonjour <strong>{$clientName}</strong>,<br><br>
      Nous avons bien reçu votre commande et elle est en cours de traitement.
      Notre équipe va préparer votre impression 3D avec le plus grand soin.
    </p>

    <!-- Récapitulatif -->
    <div style="background:#f8f9fc;border:1px solid #e8eaf0;border-radius:8px;padding:20px;margin-bottom:28px;">
      <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td style="padding:6px 0;color:#666;font-size:13px;width:50%;font-weight:600;">Numéro de commande</td>
          <td style="padding:6px 0;color:#1a1a2e;font-size:13px;text-align:right;font-weight:700;font-family:monospace;">{$numeroCommande}</td>
        </tr>
        <tr>
          <td style="padding:6px 0;color:#666;font-size:13px;font-weight:600;">Date</td>
          <td style="padding:6px 0;color:#333;font-size:13px;text-align:right;">{$orderDate}</td>
        </tr>
        <tr>
          <td style="padding:6px 0;color:#666;font-size:13px;font-weight:600;">Mode de paiement</td>
          <td style="padding:6px 0;color:#333;font-size:13px;text-align:right;">{$payment}</td>
        </tr>
      </table>
    </div>

    <!-- Articles -->
    <h3 style="color:#1a1a2e;font-size:16px;margin:0 0 12px;border-bottom:2px solid #00d4ff;padding-bottom:8px;">Articles commandés</h3>
    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin-bottom:16px;">
      <thead>
        <tr style="background:#1a1a2e;">
          <th style="padding:10px;text-align:left;color:#00d4ff;font-size:12px;font-weight:700;text-transform:uppercase;">Article</th>
          <th style="padding:10px;text-align:center;color:#00d4ff;font-size:12px;font-weight:700;text-transform:uppercase;">Qté</th>
          <th style="padding:10px;text-align:right;color:#00d4ff;font-size:12px;font-weight:700;text-transform:uppercase;">Prix unit.</th>
          <th style="padding:10px;text-align:right;color:#00d4ff;font-size:12px;font-weight:700;text-transform:uppercase;">Total</th>
        </tr>
      </thead>
      <tbody>{$itemsHtml}</tbody>
    </table>

    <!-- Total -->
    <div style="text-align:right;background:#1a1a2e;border-radius:8px;padding:16px 20px;margin-bottom:28px;">
      <span style="color:rgba(255,255,255,0.7);font-size:14px;">Total TTC : </span>
      <span style="color:#00d4ff;font-size:22px;font-weight:900;">{$total}</span>
    </div>

    <!-- Adresse -->
    <h3 style="color:#1a1a2e;font-size:16px;margin:0 0 10px;">Adresse de livraison</h3>
    <div style="background:#f8f9fc;border:1px solid #e8eaf0;border-radius:8px;padding:16px;margin-bottom:28px;color:#555;font-size:14px;line-height:1.6;white-space:pre-line;">{$address}</div>

    <!-- Info expédition -->
    <div style="background:#eff9ff;border-left:4px solid #00d4ff;border-radius:0 8px 8px 0;padding:16px 20px;margin-bottom:28px;">
      <p style="margin:0;color:#0369a1;font-size:14px;line-height:1.6;">
        📦 <strong>Suivi de votre commande</strong><br>
        Vous recevrez un email de notification dès que votre commande sera expédiée, avec votre numéro de suivi.
      </p>
    </div>

    <p style="color:#666;font-size:14px;line-height:1.7;margin:0;">
      Pour toute question concernant votre commande, n'hésitez pas à nous contacter en répondant à cet email.<br><br>
      Merci de votre confiance et à bientôt sur Al3DPrint ! 🚀
    </p>

  </td></tr>

  <!-- Footer -->
  <tr><td style="background:#1a1a2e;padding:24px 40px;text-align:center;">
    <p style="margin:0 0 6px;color:#00d4ff;font-size:16px;font-weight:700;">Al3DPrint</p>
    <p style="margin:0;color:rgba(255,255,255,0.4);font-size:11px;">Cet email est automatique, merci de ne pas y répondre directement.</p>
    <p style="margin:6px 0 0;color:rgba(255,255,255,0.3);font-size:11px;">&copy; {$currentYear} Al3DPrint. Tous droits réservés.</p>
  </td></tr>

</table>
</td></tr>
</table>
</body>
</html>
HTML;
    }

    // ─── Template : notification nouvelle commande admin ──────────────────────

    private function getAdminNewOrderTemplate(array $orderData): string
    {
        $numeroCommande = htmlspecialchars($orderData['numero_commande'] ?? ('#' . ($orderData['id'] ?? '')), ENT_QUOTES, 'UTF-8');
        $clientName     = htmlspecialchars($orderData['client']['nom'] ?? 'Inconnu', ENT_QUOTES, 'UTF-8');
        $clientEmail    = htmlspecialchars($orderData['client']['email'] ?? '', ENT_QUOTES, 'UTF-8');
        $clientPhone    = htmlspecialchars($orderData['client']['telephone'] ?? 'Non renseigné', ENT_QUOTES, 'UTF-8');
        $orderDate      = htmlspecialchars($orderData['date_commande'] ?? '', ENT_QUOTES, 'UTF-8');
        $total          = htmlspecialchars(number_format((float)($orderData['total'] ?? 0), 2, ',', ' ') . ' €', ENT_QUOTES, 'UTF-8');
        $address        = nl2br(htmlspecialchars($orderData['adresse_livraison'] ?? 'Non renseignée', ENT_QUOTES, 'UTF-8'));
        $payment        = htmlspecialchars($orderData['mode_paiement'] ?? 'Non spécifié', ENT_QUOTES, 'UTF-8');
        $currentYear    = date('Y');

        $itemsHtml = '';
        foreach ($orderData['details'] ?? [] as $item) {
            $name      = htmlspecialchars($item['produit']['nom'] ?? 'Article', ENT_QUOTES, 'UTF-8');
            $qty       = (int)($item['quantite'] ?? 1);
            $unit      = number_format((float)($item['prix_unitaire'] ?? 0), 2, ',', ' ');
            $lineTotal = number_format((float)($item['prix_unitaire'] ?? 0) * $qty, 2, ',', ' ');
            $itemsHtml .= <<<ROW
            <tr>
              <td style="padding:10px;border-bottom:1px solid #e8e8e8;color:#333;">{$name}</td>
              <td style="padding:10px;border-bottom:1px solid #e8e8e8;text-align:center;">{$qty}</td>
              <td style="padding:10px;border-bottom:1px solid #e8e8e8;text-align:right;">{$unit} €</td>
              <td style="padding:10px;border-bottom:1px solid #e8e8e8;text-align:right;font-weight:700;">{$lineTotal} €</td>
            </tr>
ROW;
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Nouvelle commande {$numeroCommande}</title></head>
<body style="margin:0;padding:0;background:#f0f2f5;font-family:Arial,Helvetica,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f2f5;padding:30px 0;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08);max-width:600px;width:100%;">

  <!-- Header admin -->
  <tr><td style="background:linear-gradient(135deg,#1a1a2e,#16213e);padding:30px 40px;text-align:center;">
    <div style="font-size:28px;font-weight:900;color:#00d4ff;margin-bottom:4px;">Al3DPrint — Admin</div>
    <div style="color:rgba(255,255,255,0.5);font-size:12px;letter-spacing:2px;text-transform:uppercase;">Notification interne</div>
  </td></tr>

  <!-- Alert banner -->
  <tr><td style="background:#f59e0b;padding:14px 40px;text-align:center;">
    <span style="color:#fff;font-size:17px;font-weight:700;">🛒 Nouvelle commande reçue</span>
  </td></tr>

  <!-- Body -->
  <tr><td style="padding:32px 40px;">

    <!-- Info commande -->
    <h3 style="margin:0 0 12px;color:#1a1a2e;font-size:16px;border-bottom:2px solid #00d4ff;padding-bottom:8px;">Détails de la commande</h3>
    <div style="background:#f8f9fc;border:1px solid #e8eaf0;border-radius:8px;padding:16px;margin-bottom:24px;">
      <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td style="padding:5px 0;color:#666;font-size:13px;width:45%;font-weight:600;">Numéro</td>
          <td style="padding:5px 0;color:#1a1a2e;font-size:13px;font-weight:700;font-family:monospace;">{$numeroCommande}</td>
        </tr>
        <tr>
          <td style="padding:5px 0;color:#666;font-size:13px;font-weight:600;">Date</td>
          <td style="padding:5px 0;color:#333;font-size:13px;">{$orderDate}</td>
        </tr>
        <tr>
          <td style="padding:5px 0;color:#666;font-size:13px;font-weight:600;">Paiement</td>
          <td style="padding:5px 0;color:#333;font-size:13px;">{$payment}</td>
        </tr>
        <tr>
          <td style="padding:5px 0;color:#666;font-size:13px;font-weight:600;">Adresse</td>
          <td style="padding:5px 0;color:#333;font-size:13px;white-space:pre-line;">{$address}</td>
        </tr>
      </table>
    </div>

    <!-- Info client -->
    <h3 style="margin:0 0 12px;color:#1a1a2e;font-size:16px;border-bottom:2px solid #4ecdc4;padding-bottom:8px;">Informations client</h3>
    <div style="background:#f0fdf9;border:1px solid #b2f0e5;border-radius:8px;padding:16px;margin-bottom:24px;">
      <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td style="padding:5px 0;color:#666;font-size:13px;width:45%;font-weight:600;">Nom</td>
          <td style="padding:5px 0;color:#333;font-size:13px;font-weight:700;">{$clientName}</td>
        </tr>
        <tr>
          <td style="padding:5px 0;color:#666;font-size:13px;font-weight:600;">Email</td>
          <td style="padding:5px 0;font-size:13px;"><a href="mailto:{$clientEmail}" style="color:#0891b2;">{$clientEmail}</a></td>
        </tr>
        <tr>
          <td style="padding:5px 0;color:#666;font-size:13px;font-weight:600;">Téléphone</td>
          <td style="padding:5px 0;color:#333;font-size:13px;">{$clientPhone}</td>
        </tr>
      </table>
    </div>

    <!-- Articles -->
    <h3 style="margin:0 0 12px;color:#1a1a2e;font-size:16px;border-bottom:2px solid #00d4ff;padding-bottom:8px;">Articles commandés</h3>
    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin-bottom:16px;">
      <thead>
        <tr style="background:#1a1a2e;">
          <th style="padding:10px;text-align:left;color:#00d4ff;font-size:12px;text-transform:uppercase;">Article</th>
          <th style="padding:10px;text-align:center;color:#00d4ff;font-size:12px;text-transform:uppercase;">Qté</th>
          <th style="padding:10px;text-align:right;color:#00d4ff;font-size:12px;text-transform:uppercase;">Prix</th>
          <th style="padding:10px;text-align:right;color:#00d4ff;font-size:12px;text-transform:uppercase;">Total</th>
        </tr>
      </thead>
      <tbody>{$itemsHtml}</tbody>
    </table>

    <!-- Total -->
    <div style="text-align:right;background:#1a1a2e;border-radius:8px;padding:16px 20px;">
      <span style="color:rgba(255,255,255,0.7);font-size:14px;">Montant total : </span>
      <span style="color:#00d4ff;font-size:24px;font-weight:900;">{$total}</span>
    </div>

  </td></tr>

  <!-- Footer -->
  <tr><td style="background:#1a1a2e;padding:20px 40px;text-align:center;">
    <p style="margin:0;color:rgba(255,255,255,0.4);font-size:11px;">Notification automatique Al3DPrint &copy; {$currentYear}</p>
  </td></tr>

</table>
</td></tr>
</table>
</body>
</html>
HTML;
    }

    // ─── Template : bienvenue nouvel inscrit ───────────────────────────────────

    private function getWelcomeTemplate(string $userName): string
    {
        $name        = htmlspecialchars($userName, ENT_QUOTES, 'UTF-8');
        $currentYear = date('Y');

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Bienvenue sur Al3DPrint</title></head>
<body style="margin:0;padding:0;background:#f0f2f5;font-family:Arial,Helvetica,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f2f5;padding:30px 0;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08);max-width:600px;width:100%;">

  <!-- Header -->
  <tr><td style="background:linear-gradient(135deg,#1a1a2e 0%,#16213e 100%);padding:40px;text-align:center;">
    <div style="font-size:36px;font-weight:900;color:#00d4ff;letter-spacing:-1px;margin-bottom:6px;">Al3DPrint</div>
    <div style="color:rgba(255,255,255,0.5);font-size:12px;letter-spacing:3px;text-transform:uppercase;">Impression 3D Professionnelle</div>
  </td></tr>

  <!-- Welcome banner -->
  <tr><td style="background:linear-gradient(90deg,#00d4ff,#4ecdc4);padding:20px 40px;text-align:center;">
    <span style="color:#fff;font-size:22px;font-weight:700;">Bienvenue, {$name} ! 🎉</span>
  </td></tr>

  <!-- Body -->
  <tr><td style="padding:40px;">

    <p style="color:#333;font-size:16px;line-height:1.8;margin:0 0 24px;">
      Bonjour <strong>{$name}</strong>,<br><br>
      Votre compte <strong>Al3DPrint</strong> a bien été créé. Nous sommes ravis de vous compter parmi nos clients !
    </p>

    <!-- Features -->
    <div style="background:#f8f9fc;border-radius:10px;padding:24px;margin-bottom:28px;">
      <p style="margin:0 0 16px;color:#1a1a2e;font-weight:700;font-size:15px;">Avec Al3DPrint, vous pouvez :</p>
      <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td style="padding:8px 0;color:#555;font-size:14px;">🖨️&nbsp;&nbsp;Commander des impressions 3D de haute qualité</td>
        </tr>
        <tr>
          <td style="padding:8px 0;color:#555;font-size:14px;">🎨&nbsp;&nbsp;Personnaliser vos produits selon vos besoins</td>
        </tr>
        <tr>
          <td style="padding:8px 0;color:#555;font-size:14px;">📦&nbsp;&nbsp;Suivre vos commandes en temps réel</td>
        </tr>
        <tr>
          <td style="padding:8px 0;color:#555;font-size:14px;">⭐&nbsp;&nbsp;Accéder à votre historique de commandes</td>
        </tr>
      </table>
    </div>

    <!-- CTA -->
    <div style="text-align:center;margin-bottom:28px;">
      <a href="https://al3dprint.fr/products" style="display:inline-block;padding:14px 36px;background:linear-gradient(135deg,#00d4ff,#4ecdc4);color:#fff;text-decoration:none;border-radius:50px;font-weight:700;font-size:15px;letter-spacing:0.5px;">
        Découvrir nos produits →
      </a>
    </div>

    <p style="color:#999;font-size:13px;text-align:center;margin:0;">
      Si vous n'êtes pas à l'origine de cette inscription, ignorez cet email.
    </p>

  </td></tr>

  <!-- Footer -->
  <tr><td style="background:#1a1a2e;padding:24px 40px;text-align:center;">
    <p style="margin:0 0 6px;color:#00d4ff;font-size:16px;font-weight:700;">Al3DPrint</p>
    <p style="margin:0;color:rgba(255,255,255,0.4);font-size:11px;">Cet email est automatique, merci de ne pas y répondre directement.</p>
    <p style="margin:6px 0 0;color:rgba(255,255,255,0.3);font-size:11px;">&copy; {$currentYear} Al3DPrint. Tous droits réservés.</p>
  </td></tr>

</table>
</td></tr>
</table>
</body>
</html>
HTML;
    }

    // ─── Notification nouvelle demande de personnalisation ────────────────────

    public function sendCustomOrderNotification(array $demandeData): void
    {
        $productName = $demandeData['product_name'] ?? 'Produit personnalisé';

        $email = (new Email())
            ->from($this->fromEmail)
            ->to($this->adminEmail)
            ->subject("🎨 Nouvelle demande de personnalisation – {$productName}")
            ->html($this->getCustomOrderNotificationTemplate($demandeData));

        $this->mailer->send($email);
    }

    private function getCustomOrderNotificationTemplate(array $data): string
    {
        $product     = htmlspecialchars($data['product_name'] ?? '', ENT_QUOTES, 'UTF-8');
        $nom         = htmlspecialchars($data['client_nom']   ?? 'Client', ENT_QUOTES, 'UTF-8');
        $email       = htmlspecialchars($data['client_email'] ?? '', ENT_QUOTES, 'UTF-8');
        $nbFiles     = count($data['fichiers'] ?? []);
        $demandeId   = (int) ($data['id'] ?? 0);
        $currentYear = date('Y');

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Nouvelle demande – {$product}</title></head>
<body style="margin:0;padding:0;background:#f0f2f5;font-family:Arial,Helvetica,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f2f5;padding:30px 0;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08);max-width:600px;width:100%;">
  <tr><td style="background:linear-gradient(135deg,#1a1a2e,#16213e);padding:30px 40px;text-align:center;">
    <div style="font-size:28px;font-weight:900;color:#00d4ff;margin-bottom:4px;">Al3DPrint — Admin</div>
    <div style="color:rgba(255,255,255,0.5);font-size:12px;letter-spacing:2px;text-transform:uppercase;">Commande personnalisée</div>
  </td></tr>
  <tr><td style="background:#7c3aed;padding:14px 40px;text-align:center;">
    <span style="color:#fff;font-size:17px;font-weight:700;">🎨 Nouvelle demande de personnalisation #{$demandeId}</span>
  </td></tr>
  <tr><td style="padding:32px 40px;">
    <p style="color:#333;font-size:15px;margin:0 0 20px;">Un client a soumis <strong>{$nbFiles} photo(s)</strong> pour personnaliser un produit.</p>
    <div style="background:#f8f9fc;border:1px solid #e8eaf0;border-radius:8px;padding:16px;margin-bottom:24px;">
      <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td style="padding:7px 0;color:#666;font-size:13px;width:40%;font-weight:600;">Produit</td>
          <td style="padding:7px 0;color:#1a1a2e;font-size:13px;font-weight:700;">{$product}</td>
        </tr>
        <tr>
          <td style="padding:7px 0;color:#666;font-size:13px;font-weight:600;">Client</td>
          <td style="padding:7px 0;color:#333;font-size:13px;">{$nom}</td>
        </tr>
        <tr>
          <td style="padding:7px 0;color:#666;font-size:13px;font-weight:600;">Email</td>
          <td style="padding:7px 0;font-size:13px;"><a href="mailto:{$email}" style="color:#0891b2;">{$email}</a></td>
        </tr>
        <tr>
          <td style="padding:7px 0;color:#666;font-size:13px;font-weight:600;">Photos</td>
          <td style="padding:7px 0;color:#7c3aed;font-size:13px;font-weight:700;">{$nbFiles} image(s) disponible(s) dans le panel admin</td>
        </tr>
      </table>
    </div>
    <div style="background:#f0fdf4;border-left:4px solid #10b981;border-radius:0 8px 8px 0;padding:14px 18px;">
      <p style="margin:0;color:#065f46;font-size:14px;">📁 Les images sont accessibles dans le <strong>panel admin → Demandes perso</strong>. Contactez le client pour finaliser.</p>
    </div>
  </td></tr>
  <tr><td style="background:#1a1a2e;padding:20px 40px;text-align:center;">
    <p style="margin:0;color:rgba(255,255,255,0.4);font-size:11px;">Notification automatique Al3DPrint &copy; {$currentYear}</p>
  </td></tr>
</table>
</td></tr>
</table>
</body>
</html>
HTML;
    }

    // ─── Envoi des fichiers personnalisés à l'admin ───────────────────────────

    public function sendCustomFilesEmail(string $productName, string $clientEmail, string $clientNom, array $files): void
    {
        $email = (new Email())
            ->from($this->fromEmail)
            ->to($this->adminEmail)
            ->subject("📎 Nouvelles images personnalisées – {$productName}")
            ->html($this->getCustomFilesTemplate($productName, $clientEmail, $clientNom, count($files)));

        foreach ($files as $file) {
            $email->attachFromPath($file['path'], $file['name'], $file['mime']);
        }

        $this->mailer->send($email);
    }

    private function getCustomFilesTemplate(string $productName, string $clientEmail, string $clientNom, int $fileCount): string
    {
        $product     = htmlspecialchars($productName, ENT_QUOTES, 'UTF-8');
        $email       = htmlspecialchars($clientEmail,  ENT_QUOTES, 'UTF-8');
        $nom         = htmlspecialchars($clientNom,    ENT_QUOTES, 'UTF-8');
        $currentYear = date('Y');

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Images personnalisées – {$product}</title></head>
<body style="margin:0;padding:0;background:#f0f2f5;font-family:Arial,Helvetica,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f2f5;padding:30px 0;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08);max-width:600px;width:100%;">
  <tr><td style="background:linear-gradient(135deg,#1a1a2e,#16213e);padding:30px 40px;text-align:center;">
    <div style="font-size:28px;font-weight:900;color:#00d4ff;margin-bottom:4px;">Al3DPrint — Admin</div>
    <div style="color:rgba(255,255,255,0.5);font-size:12px;letter-spacing:2px;text-transform:uppercase;">Commande personnalisée</div>
  </td></tr>
  <tr><td style="background:#7c3aed;padding:14px 40px;text-align:center;">
    <span style="color:#fff;font-size:17px;font-weight:700;">📎 Nouvelles images reçues d'un client</span>
  </td></tr>
  <tr><td style="padding:32px 40px;">
    <p style="color:#333;font-size:15px;margin:0 0 20px;">Un client a soumis des images pour personnaliser un produit.</p>
    <div style="background:#f8f9fc;border:1px solid #e8eaf0;border-radius:8px;padding:16px;margin-bottom:24px;">
      <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td style="padding:7px 0;color:#666;font-size:13px;width:40%;font-weight:600;">Produit</td>
          <td style="padding:7px 0;color:#1a1a2e;font-size:13px;font-weight:700;">{$product}</td>
        </tr>
        <tr>
          <td style="padding:7px 0;color:#666;font-size:13px;font-weight:600;">Client</td>
          <td style="padding:7px 0;color:#333;font-size:13px;">{$nom}</td>
        </tr>
        <tr>
          <td style="padding:7px 0;color:#666;font-size:13px;font-weight:600;">Email</td>
          <td style="padding:7px 0;font-size:13px;"><a href="mailto:{$email}" style="color:#0891b2;">{$email}</a></td>
        </tr>
        <tr>
          <td style="padding:7px 0;color:#666;font-size:13px;font-weight:600;">Fichiers joints</td>
          <td style="padding:7px 0;color:#7c3aed;font-size:13px;font-weight:700;">{$fileCount} image(s)</td>
        </tr>
      </table>
    </div>
    <div style="background:#f0fdf4;border-left:4px solid #10b981;border-radius:0 8px 8px 0;padding:14px 18px;">
      <p style="margin:0;color:#065f46;font-size:14px;">Les images sont jointes à cet email. Contactez le client pour finaliser sa commande personnalisée.</p>
    </div>
  </td></tr>
  <tr><td style="background:#1a1a2e;padding:20px 40px;text-align:center;">
    <p style="margin:0;color:rgba(255,255,255,0.4);font-size:11px;">Notification automatique Al3DPrint &copy; {$currentYear}</p>
  </td></tr>
</table>
</td></tr>
</table>
</body>
</html>
HTML;
    }

    private function getPasswordResetEmailTemplate(string $userName, string $resetUrl, string $resetToken): string
    {
        $userNameEscaped = htmlspecialchars($userName, ENT_QUOTES, 'UTF-8');
        $resetUrlEscaped = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');
        $resetTokenEscaped = htmlspecialchars($resetToken, ENT_QUOTES, 'UTF-8');
        $currentYear = date("Y");
        
        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialisation de mot de passe</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .email-container {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #00d4ff;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #00d4ff;
            margin-bottom: 10px;
        }
        .content {
            margin-bottom: 30px;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #00d4ff, #4ecdc4);
            color: #ffffff;
            text-decoration: none;
            border-radius: 25px;
            font-weight: bold;
            margin: 20px 0;
            text-align: center;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        .warning {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .token-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            font-family: monospace;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="logo">Al3DPrint</div>
            <h1>Réinitialisation de mot de passe</h1>
        </div>

        <div class="content">
            <p>Bonjour {$userNameEscaped},</p>
            
            <p>Vous avez demandé à réinitialiser votre mot de passe. Cliquez sur le bouton ci-dessous pour créer un nouveau mot de passe :</p>

            <div style="text-align: center;">
                <a href="{$resetUrlEscaped}" class="button">Réinitialiser mon mot de passe</a>
            </div>

            <p>Ou copiez et collez ce lien dans votre navigateur :</p>
            <div class="token-info">{$resetUrlEscaped}</div>

            <div class="warning">
                <strong>⚠️ Important :</strong> Ce lien est valide pendant 1 heure. Si vous n'avez pas demandé cette réinitialisation, ignorez cet email.
            </div>

            <p>Si le bouton ne fonctionne pas, vous pouvez également utiliser ce token de réinitialisation :</p>
            <div class="token-info">{$resetTokenEscaped}</div>
        </div>

        <div class="footer">
            <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
            <p>&copy; {$currentYear} Al3DPrint. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }
}

