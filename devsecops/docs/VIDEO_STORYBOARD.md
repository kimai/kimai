# Video Demonstration Storyboard (10-15 minutes)

Formal presentation: follow the slides, keep the main screen on the
demo material and the speaker screen on the presenter, clear audio.
Record every segment live - no borrowed footage (include your terminal
prompt/username and the current date in frame where practical).

## Slides (submit with the video)

1. Title & team
2. Architecture of the pipeline (PIPELINE.md §1 diagram)
3. Tools & justification (one line per tool)
4. Threat model & WSTG mapping
5. Secure deployment architecture
6. Bug report & disclosure (GHSA-pvc4-crg3-gj44)
7. Developer reuse & collaboration evidence
8. Maintenance & lessons learned

## Scenes

| # | Time | Screen | Action |
|---|---|---|---|
| 1 | 0:00-1:00 | slides 1-2 | Introduce the project and the pipeline diagram |
| 2 | 1:00-3:00 | terminal + workflow file | Show `.github/workflows/devsecops.yml`; walk the stages; open the Actions tab showing a live run on the self-hosted runner |
| 3 | 3:00-5:00 | terminal | Run the WSTG suite live: `vendor/bin/phpunit --group security tests/SecurityTesting/`; show the Semgrep ruleset + a live scan; show `composer audit` |
| 4 | 5:00-6:30 | browser + editor | Bug story: show the advisory (redacted), reproduce the export 403/200 difference with and without the fix (`git stash` trick or two terminals), show the regression tests passing |
| 5 | 6:30-9:00 | terminal + browser | Deployment: run `devsecops/deploy/deploy.sh`; show the compose stack (`docker compose ps`); browse the app over HTTPS; show the HTTP->HTTPS redirect |
| 6 | 9:00-11:00 | terminal | Post-deployment validation: run `smoke-test.sh` and `security-audit.sh` live; open the ZAP baseline report; show the report directory |
| 7 | 11:00-12:30 | browser | Notification: trigger or replay a failing run; show the GitHub issue created by the pipeline (and the de-duplication comment); show the webhook message if configured |
| 8 | 12:30-14:00 | slides 6-8 | Collaboration evidence (redacted), reuse discussion, maintenance guide summary, lessons learned |
| 9 | 14:00-15:00 | slide | Wrap-up and Q&A pointer |

## Recording checklist

- [ ] 1080p, clear audio, speaker visible
- [ ] terminal font large enough to read on a phone
- [ ] every student records the same-length video individually
- [ ] slides submitted alongside the MP4
- [ ] redact tokens/passwords from every terminal capture (use demo values)
