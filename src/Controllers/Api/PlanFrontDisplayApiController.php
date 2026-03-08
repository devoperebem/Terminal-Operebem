<?php
/**
 * PlanFrontDisplayApiController
 * 
 * Public API endpoint for satellite systems (Portal do Aluno, Diário de Trades)
 * to fetch their pricing display configuration from the Terminal (source of truth).
 * 
 * GET /api/plans/front-display?system=portal_aluno
 * Returns JSON with plan display configs for the requested system.
 */

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Core\Database;

class PlanFrontDisplayApiController extends BaseController
{
    private const VALID_SYSTEMS = ['terminal', 'portal_aluno', 'diario_trades'];
    private const CACHE_TTL = 300; // 5 minutes

    /**
     * GET /api/plans/front-display?system={system_key}
     * 
     * Returns all visible plan display configs for a given system.
     * Public endpoint with cache headers.
     */
    public function index(): void
    {
        $systemKey = trim((string)($_GET['system'] ?? ''));

        if ($systemKey === '' || !in_array($systemKey, self::VALID_SYSTEMS, true)) {
            $this->json([
                'success' => false,
                'message' => 'Invalid or missing system parameter. Valid: ' . implode(', ', self::VALID_SYSTEMS),
            ], 422);
            return;
        }

        try {
            $rows = Database::fetchAll(
                'SELECT 
                    pfd.id,
                    pfd.plan_id,
                    sp.slug AS plan_slug,
                    sp.tier,
                    sp.is_active AS plan_active,
                    pfd.display_name,
                    pfd.price_display,
                    pfd.price_subtitle,
                    pfd.description,
                    pfd.features,
                    pfd.cta_label,
                    pfd.cta_url,
                    pfd.is_highlighted,
                    pfd.badge_text,
                    pfd.display_order,
                    pfd.metadata
                 FROM plan_front_display pfd
                 JOIN subscription_plans sp ON sp.id = pfd.plan_id
                 WHERE pfd.system_key = ?
                   AND pfd.is_visible = TRUE
                   AND sp.is_active = TRUE
                 ORDER BY pfd.display_order ASC, pfd.id ASC',
                [$systemKey]
            );

            $plans = [];
            foreach ($rows as $row) {
                $plans[] = [
                    'id'             => (int)$row['id'],
                    'plan_id'        => (int)$row['plan_id'],
                    'plan_slug'      => $row['plan_slug'],
                    'tier'           => $row['tier'],
                    'display_name'   => $row['display_name'] ?? $row['plan_slug'],
                    'price_display'  => $row['price_display'] ?? '',
                    'price_subtitle' => $row['price_subtitle'] ?? '',
                    'description'    => $row['description'] ?? '',
                    'features'       => json_decode($row['features'] ?? '[]', true) ?: [],
                    'cta_label'      => $row['cta_label'] ?? 'Assinar',
                    'cta_url'        => $row['cta_url'] ?? '',
                    'is_highlighted' => (bool)$row['is_highlighted'],
                    'badge_text'     => $row['badge_text'] ?? '',
                    'display_order'  => (int)$row['display_order'],
                    'metadata'       => json_decode($row['metadata'] ?? '{}', true) ?: [],
                ];
            }

            header('Cache-Control: public, max-age=' . self::CACHE_TTL);
            $this->json(['success' => true, 'system' => $systemKey, 'plans' => $plans]);
        } catch (\Throwable $e) {
            error_log('[PlanFrontDisplayApi] Error: ' . $e->getMessage());
            $this->json(['success' => false, 'message' => 'Internal error'], 500);
        }
    }
}
