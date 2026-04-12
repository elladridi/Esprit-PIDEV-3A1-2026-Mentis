<?php

namespace App\Service;

use App\Entity\AssessmentResult;
use Dompdf\Dompdf;
use Dompdf\Options;

class PdfExportService
{
    public function generateResultPdf(
        AssessmentResult $result,
        string $aiAnalysis,
        ?object $user,
        ?object $assessment
    ): string {

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);

        $html = $this->buildHtml($result, $aiAnalysis, $user, $assessment);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    private function buildHtml(
        AssessmentResult $result,
        string $aiAnalysis,
        ?object $user,
        ?object $assessment
    ): string {

        $riskLevel    = $result->getRiskLevel() ?? 'N/A';
        $totalScore   = $result->getTotalScore() ?? 0;
        $takenAt      = $result->getTakenAt() ? $result->getTakenAt()->format('Y-m-d') : 'N/A';
        $interpretation = $result->getInterpretation() ?? '';
        $recommended  = $result->getRecommendedContent() ?? '';
        $suggestSession = $result->isSuggestSession() ? 'Yes' : 'No';

        $userName       = $user ? ($user->getFirstname() . ' ' . $user->getLastname()) : 'N/A';
        $assessmentTitle = $assessment ? $assessment->getTitle() : 'N/A';
        $assessmentType  = $assessment ? $assessment->getType() : 'N/A';

        $riskColor = match(strtolower($riskLevel)) {
            'high', 'severe'       => '#c0392b',
            'moderate', 'mild'     => '#e67e22',
            default                => '#27ae60',
        };

        $isCritical = in_array(strtolower($riskLevel), ['high', 'severe']);

        // Clean AI analysis
        $cleanAI = htmlspecialchars(
            str_replace(['**', '###', '##', '# ', '•'], ['', '', '', '', '→'], $aiAnalysis)
        );

        $cleanRecommended = htmlspecialchars($recommended);
        $cleanInterpretation = htmlspecialchars($interpretation);

        $emergencySection = '';
        if ($isCritical) {
            $emergencySection = '
            <div style="background:#fff3f3; border:2px solid #d32f2f; border-radius:6px;
                        padding:15px 20px; margin-bottom:25px;">
                <h3 style="color:#d32f2f; margin:0 0 10px 0;">🚨 Emergency Resources</h3>
                <p style="margin:4px 0; font-size:13px;">Emergency Services: 911 (US) / 112 (EU) / 000 (AU)</p>
                <p style="margin:4px 0; font-size:13px;">Mental Health Crisis (USA): 988</p>
                <p style="margin:4px 0; font-size:13px;">Crisis Text Line: Text HOME to 741741</p>
                <p style="margin:4px 0; font-size:13px;">France: 3114 | Germany: 0800 111 0 111 | Canada: 1-833-456-4566</p>
                <p style="margin:4px 0; font-size:13px;">Online: befrienders.org | iasp.info</p>
            </div>';
        }

        return '<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 13px;
        color: #333;
        background: white;
        padding: 0;
    }

    .header {
        background: linear-gradient(135deg, #3c7860 0%, #6c9e83 100%);
        color: white;
        padding: 30px 40px;
        margin-bottom: 30px;
    }
    .header h1 {
        font-size: 28px;
        margin-bottom: 5px;
        letter-spacing: 1px;
    }
    .header p {
        font-size: 13px;
        opacity: 0.85;
    }
    .header .logo {
        font-size: 32px;
        font-weight: bold;
        margin-bottom: 8px;
    }

    .content { padding: 0 40px 40px 40px; }

    .critical-banner {
        background: #d32f2f;
        color: white;
        padding: 14px 20px;
        border-radius: 6px;
        margin-bottom: 25px;
        font-weight: bold;
        font-size: 14px;
        text-align: center;
    }

    .section {
        margin-bottom: 25px;
        page-break-inside: avoid;
    }
    .section-title {
        font-size: 16px;
        font-weight: bold;
        color: #3c7860;
        border-bottom: 2px solid #c8dcd2;
        padding-bottom: 6px;
        margin-bottom: 15px;
    }

    .stats-grid {
        display: table;
        width: 100%;
        margin-bottom: 20px;
        border-collapse: separate;
        border-spacing: 10px;
    }
    .stat-box {
        display: table-cell;
        width: 33%;
        background: #f0f8f5;
        border-radius: 8px;
        padding: 15px;
        text-align: center;
    }
    .stat-value {
        font-size: 28px;
        font-weight: bold;
        margin-bottom: 5px;
    }
    .stat-label {
        font-size: 11px;
        color: #888;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .info-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 15px;
    }
    .info-table td {
        padding: 8px 12px;
        border-bottom: 1px solid #eee;
        font-size: 13px;
    }
    .info-table td:first-child {
        font-weight: bold;
        color: #555;
        width: 40%;
    }

    .text-block {
        background: #f9f9f9;
        border-left: 4px solid #6c9e83;
        padding: 15px;
        border-radius: 0 5px 5px 0;
        line-height: 1.7;
        font-size: 13px;
        white-space: pre-wrap;
    }

    .recommendations-block {
        background: #f0f8f5;
        border: 1px solid #c8dcd2;
        border-radius: 5px;
        padding: 15px;
        line-height: 1.8;
        white-space: pre-wrap;
    }

    .disclaimer {
        background: #f9f9f9;
        border: 1px solid #ddd;
        border-radius: 5px;
        padding: 15px;
        font-size: 11px;
        color: #888;
        margin-top: 30px;
        line-height: 1.6;
    }

    .footer {
        margin-top: 30px;
        padding-top: 15px;
        border-top: 2px solid #c8dcd2;
        text-align: center;
        font-size: 11px;
        color: #aaa;
    }

    .badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: bold;
    }
</style>
</head>
<body>

<div class="header">
    <div class="logo">🌿 Mentis</div>
    <h1>Mental Health Assessment Report</h1>
    <p>Generated on ' . date('F j, Y \a\t H:i') . ' | Confidential</p>
</div>

<div class="content">

    ' . ($isCritical ? '<div class="critical-banner">⚠️ CRITICAL RISK DETECTED — Please seek immediate professional support</div>' : '') . '

    ' . $emergencySection . '

    <!-- ASSESSMENT SUMMARY -->
    <div class="section">
        <div class="section-title">📊 Assessment Summary</div>

        <table class="stats-grid" style="width:100%">
            <tr>
                <td style="width:33%; background:#f0f8f5; border-radius:8px; padding:15px; text-align:center; vertical-align:middle;">
                    <div style="font-size:36px; font-weight:bold; color:#6c9e83;">' . $totalScore . '</div>
                    <div style="font-size:11px; color:#888; margin-top:4px;">TOTAL SCORE</div>
                </td>
                <td style="width:33%; background:#f0f8f5; border-radius:8px; padding:15px; text-align:center; vertical-align:middle;">
                    <div style="font-size:26px; font-weight:bold; color:' . $riskColor . ';">' . $riskLevel . '</div>
                    <div style="font-size:11px; color:#888; margin-top:4px;">RISK LEVEL</div>
                </td>
                <td style="width:33%; background:#f0f8f5; border-radius:8px; padding:15px; text-align:center; vertical-align:middle;">
                    <div style="font-size:22px; font-weight:bold; color:' . ($result->isSuggestSession() ? '#c0392b' : '#27ae60') . ';">' . $suggestSession . '</div>
                    <div style="font-size:11px; color:#888; margin-top:4px;">SESSION SUGGESTED</div>
                </td>
            </tr>
        </table>

        <table class="info-table">
            <tr>
                <td>Patient</td>
                <td>' . htmlspecialchars($userName) . '</td>
            </tr>
            <tr>
                <td>Assessment</td>
                <td>' . htmlspecialchars($assessmentTitle) . ' (' . htmlspecialchars($assessmentType) . ')</td>
            </tr>
            <tr>
                <td>Date Taken</td>
                <td>' . $takenAt . '</td>
            </tr>
            <tr>
                <td>Result ID</td>
                <td>#' . $result->getResultId() . '</td>
            </tr>
        </table>
    </div>

    <!-- INTERPRETATION -->
    <div class="section">
        <div class="section-title">🔍 Interpretation</div>
        <div class="text-block">' . $cleanInterpretation . '</div>
    </div>

    ' . ($cleanAI ? '
    <!-- AI ANALYSIS -->
    <div class="section">
        <div class="section-title">🤖 AI Analysis</div>
        <div class="text-block">' . $cleanAI . '</div>
    </div>
    ' : '') . '

    <!-- RECOMMENDATIONS -->
    <div class="section">
        <div class="section-title">💡 Personalized Recommendations</div>
        <div class="recommendations-block">' . $cleanRecommended . '</div>
    </div>

    <!-- DISCLAIMER -->
    <div class="disclaimer">
        <strong>⚠️ Medical Disclaimer:</strong> This report is generated by the Mentis mental health
        assessment platform and is intended for informational and self-awareness purposes only.
        It does not constitute a clinical diagnosis, medical advice, or professional mental health
        treatment. The results should not be used as a substitute for consultation with a qualified
        mental health professional. If you are experiencing significant distress or a mental health
        crisis, please contact a licensed healthcare provider or emergency services immediately.
    </div>

    <div class="footer">
        Mentis Mental Health Platform &nbsp;|&nbsp;
        Report ID: MR-' . $result->getResultId() . '-' . date('Ymd') . ' &nbsp;|&nbsp;
        Confidential
    </div>

</div>
</body>
</html>';
    }
}