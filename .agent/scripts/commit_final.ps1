git add .
git commit -m "feat(admin): subscription management improvements (refund, sync, email, fixes)"
git push
Remove-Item .agent/scripts/fix_user_view.php -ErrorAction SilentlyContinue
Remove-Item .agent/scripts/fix_plans.php -ErrorAction SilentlyContinue
exit 0
