<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique des Connexions - SERDI</title>
    <style>
        @page {
            margin: 100px 50px 80px 50px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', 'DejaVu Sans', Arial, sans-serif;
            font-size: 9px;
            color: #2c3e50;
            line-height: 1.4;
            background: #ffffff;
        }

        /* Filigrane optimisé - Visible mais non intrusif */
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            opacity: 0.08;
            z-index: 0;
            pointer-events: none;
        }

        .watermark-content {
            text-align: center;
        }

        .watermark img {
            width: 400px;
            height: auto;
            display: block;
            margin: 0 auto 20px;
            opacity: 0.6;
        }

        .watermark-text {
            font-size: 48px;
            font-weight: 700;
            color: #1e3a8a;
            letter-spacing: 8px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .watermark-subtext {
            font-size: 24px;
            color: #3b82f6;
            letter-spacing: 4px;
        }

        /* Contenu principal - Z-index supérieur */
        .content-wrapper {
            position: relative;
            z-index: 1;
            background: transparent;
        }

        /* En-tête fixe */
        header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            color: white;
            padding: 20px 50px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .logo-header {
            width: 70px;
            height: 70px;
            background: white;
            padding: 8px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .header-title h1 {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 5px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }

        .header-title .subtitle {
            font-size: 11px;
            opacity: 0.9;
            font-weight: 300;
        }

        .header-right {
            text-align: right;
            background: rgba(255, 255, 255, 0.15);
            padding: 12px 18px;
            border-radius: 8px;
            backdrop-filter: blur(10px);
        }

        .meta-info {
            font-size: 9px;
            line-height: 1.6;
        }

        .meta-info strong {
            font-weight: 600;
            margin-right: 5px;
        }

        /* Section informations */
        .info-section {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border-left: 5px solid #3b82f6;
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 10px;
        }

        .info-item {
            text-align: center;
        }

        .info-label {
            font-size: 8px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .info-value {
            font-size: 18px;
            font-weight: 700;
            color: #1e40af;
        }

        /* Tableau amélioré */
        .table-container {
            margin: 25px 0;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            background: white;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }

        thead {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            color: white;
        }

        th {
            padding: 10px 8px;
            text-align: left;
            font-weight: 600;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-right: 1px solid rgba(255, 255, 255, 0.15);
        }

        th:last-child {
            border-right: none;
        }

        td {
            padding: 8px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 8px;
        }

        tbody tr {
            transition: background-color 0.2s;
        }

        tbody tr:nth-child(odd) {
            background: #ffffff;
        }

        tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        tbody tr:hover {
            background: #eff6ff;
        }

        /* Badges professionnels */
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 7px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .badge-primary {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #93c5fd;
        }

        .badge-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }

        .badge-warning {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fcd34d;
        }

        .badge-info {
            background: #e0e7ff;
            color: #3730a3;
            border: 1px solid #a5b4fc;
        }

        /* Pied de page fixe */
        footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            padding: 15px 50px;
            border-top: 3px solid #3b82f6;
            font-size: 8px;
            color: #64748b;
            z-index: 1000;
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .footer-left {
            font-weight: 600;
        }

        .footer-right {
            text-align: right;
        }

        /* Séparateur de section */
        .section-divider {
            height: 3px;
            background: linear-gradient(90deg, #3b82f6 0%, transparent 100%);
            margin: 20px 0;
            border-radius: 2px;
        }

        /* Colonnes optimisées */
        .col-name { width: 10%; }
        .col-email { width: 16%; }
        .col-dept { width: 11%; }
        .col-role { width: 11%; }
        .col-date { width: 9%; }
        .col-time { width: 7%; }
        .col-duration { width: 8%; }
        .col-ip { width: 11%; }

        /* Message sans données */
        .no-data {
            text-align: center;
            padding: 60px 20px;
            background: #f8fafc;
            border-radius: 10px;
            margin: 30px 0;
        }

        .no-data-icon {
            font-size: 48px;
            color: #cbd5e1;
            margin-bottom: 15px;
        }

        .no-data-text {
            font-size: 14px;
            color: #64748b;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <!-- Filigrane professionnel -->
    <div class="watermark">
        <div class="watermark-content">
            <img src="data:image/jpg;base64,{{ base64_encode(file_get_contents(public_path('images/serdi-logo.jpg'))) }}" alt="SERDI">
            <div class="watermark-text">SERDI</div>
            <div class="watermark-subtext">CONFIDENTIEL</div>
        </div>
    </div>

    <!-- En-tête fixe -->
    <header>
        <div class="header-container">
            <div class="header-left">
                <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/serdi-logo.jpg'))) }}" 
                     alt="SERDI Logo" 
                     class="logo-header">
                <div class="header-title">
                    <h1>📊 HISTORIQUE DES CONNEXIONS</h1>
                    <div class="subtitle">Rapport analytique du suivi des connexions utilisateurs</div>
                </div>
            </div>
            <div class="header-right">
                <div class="meta-info">
                    <div><strong>📅 Généré le:</strong> {{ $date_generation }}</div>
                    <div><strong>📆 Période:</strong> {{ $periode }}</div>
                    <div><strong>🔢 Enregistrements:</strong> {{ $total }}</div>
                </div>
            </div>
        </div>
    </header>

    <!-- Contenu principal -->
    <div class="content-wrapper">
        <!-- Section informations récapitulatives -->
        <div class="info-section">
            <strong style="font-size: 11px; color: #1e40af;">📈 VUE D'ENSEMBLE</strong>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Total Connexions</div>
                    <div class="info-value">{{ $total }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Période Analysée</div>
                    <div class="info-value" style="font-size: 12px;">{{ $periode }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Date Génération</div>
                    <div class="info-value" style="font-size: 11px;">{{ $date_generation }}</div>
                </div>
            </div>
        </div>

        <div class="section-divider"></div>

        <!-- Tableau des données -->
        <div class="table-container">
            @if($connections->count() > 0)
                <table>
                    <thead>
                        <tr>
                            <th class="col-name">👤 Nom</th>
                            <th class="col-name">Prénom</th>
                            <th class="col-email">📧 Email</th>
                            <th class="col-dept">🏢 Département</th>
                            <th class="col-role">💼 Poste</th>
                            <th class="col-date">📅 Connexion</th>
                            <th class="col-time">🕐 Heure</th>
                            <th class="col-date">📅 Déconnexion</th>
                            <th class="col-time">🕐 Heure</th>
                            <th class="col-duration">⏱️ Durée</th>
                            <th class="col-ip">🌐 Adresse IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($connections as $connection)
                            <tr>
                                <td><strong>{{ $connection->nom }}</strong></td>
                                <td>{{ $connection->prenom }}</td>
                                <td style="color: #3b82f6;">{{ $connection->email_pro }}</td>
                                <td>
                                    <span class="badge badge-primary">{{ $connection->departement }}</span>
                                </td>
                                <td>
                                    <span class="badge badge-info">{{ $connection->poste }}</span>
                                </td>
                                <td style="font-weight: 500;">
                                    {{ $connection->login_at ? \Carbon\Carbon::parse($connection->login_at)->format('d/m/Y') : '-' }}
                                </td>
                                <td style="color: #059669;">
                                    {{ $connection->login_at ? \Carbon\Carbon::parse($connection->login_at)->format('H:i:s') : '-' }}
                                </td>
                                <td style="font-weight: 500;">
                                    {{ $connection->logout_at ? \Carbon\Carbon::parse($connection->logout_at)->format('d/m/Y') : '-' }}
                                </td>
                                <td style="color: #dc2626;">
                                    {{ $connection->logout_at ? \Carbon\Carbon::parse($connection->logout_at)->format('H:i:s') : '-' }}
                                </td>
                                <td style="text-align: center;">
                                    @if($connection->session_duration)
                                        <span class="badge badge-success">{{ round($connection->session_duration / 60, 2) }} min</span>
                                    @else
                                        <span class="badge badge-warning">En cours</span>
                                    @endif
                                </td>
                                <td style="font-family: 'Courier New', monospace; font-size: 7px; color: #6366f1;">
                                    {{ $connection->ip_address ?? '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="no-data">
                    <div class="no-data-icon">📭</div>
                    <div class="no-data-text">Aucune donnée de connexion disponible pour la période sélectionnée</div>
                </div>
            @endif
        </div>
    </div>

    <!-- Pied de page fixe -->
    <footer>
        <div class="footer-content">
            <div class="footer-left">
                <strong>🔒 Document Confidentiel</strong> - SERDI © {{ date('Y') }} - Tous droits réservés
            </div>
            <div class="footer-right">
                Généré automatiquement le {{ $date_generation }}
            </div>
        </div>
    </footer>

    <!-- Script pour numérotation des pages -->
    <script type="text/php">
        if (isset($pdf)) {
            $text = "Page {PAGE_NUM} sur {PAGE_COUNT}";
            $size = 8;
            $font = $fontMetrics->getFont("DejaVu Sans", "normal");
            $width = $fontMetrics->get_text_width($text, $font, $size);
            $x = ($pdf->get_width() - $width) / 2;
            $y = $pdf->get_height() - 50;
            $pdf->page_text($x, $y, $text, $font, $size, array(0.4, 0.4, 0.4));
        }
    </script>
</body>
</html>