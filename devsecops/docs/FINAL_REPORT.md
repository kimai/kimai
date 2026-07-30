# Final Report Structure (Springer template)

> Build the final PDF from this skeleton using the Springer proceedings
> template (LNCS format, see the course link). Export to PDF and check the
> AODA accessibility items listed at the end before submitting.

## Title page

- Title: Automated Cybersecurity Testing & DevSecOps Integration for the Kimai Time-Tracking Application
- Author(s), course, date

## Abstract (150-250 words)

One paragraph: problem (manual security testing does not scale), approach
(on-premise DevSecOps pipeline with SAST, SCA, WSTG-mapped security tests,
hardened automated deployment, DAST, developer notification), key results
(32 automated WSTG tests, hardened compose deployment, one maintainer-triaged
bug with a merged-style fix on the fork), conclusion.

## 1. Introduction

- Context: DevSecOps, shift-left security, why open-source projects benefit
- Objectives and scope (on-premise, Kimai, OWASP WSTG v4.2 Section 4)
- Structure of the report

## 2. Background and related work

- OWASP WSTG v4.2 categories used (authentication, session, authorization,
  input validation, error handling, configuration)
- Kimai architecture summary (Symfony 6.4, Doctrine, JSON API, Twig)
- Tool landscape and selection rationale (short version of PIPELINE.md §2)

## 3. Pipeline architecture and configuration

- Diagram (reuse PIPELINE.md §1)
- Stage-by-stage description, gates vs. audit-mode decisions
- On-premise runner design and secrets handling
- Developer notification design (de-duplication, channels)

## 4. Security testing coverage and justification

- Threat model table (TEST_CASES.md §summary)
- Per-category test cases with WSTG IDs and rationale (condensed TEST_CASES.md)
- SAST/SCA coverage and the gating ruleset (incl. the false-positive triage example)

## 5. Automated secure deployment

- Architecture diagram (DEPLOYMENT.md §1)
- Hardening table (DEPLOYMENT.md §3) and the CSP Report-Only trade-off discussion
- Post-deployment validation results (smoke test, configuration audit, ZAP baseline)

## 6. Bug report and responsible disclosure

- GHSA-pvc4-crg3-gj44: summary, PoC, root cause, fix, regression tests, timeline
- GHSA-pjrx-mwv9-j9vf: the rejected report and the lesson (documented behavior)
- Disclosure process followed (BUG_REPORT.md §4)

## 7. Developer feedback and reuse

- Collaboration log (COLLABORATION.md §1)
- Reuse analysis (COLLABORATION.md §2) and the maintainers' response (§3)
- Screenshots (redacted) in the appendix

## 8. Pipeline maintenance

- Summary of MAINTENANCE.md: adding tests, updating tools, interpreting
  reports, version compatibility strategy

## 9. Lessons learned

- Gating security rules must be near-zero false positives (Semgrep example)
- "Boot the kernel once per test" and other framework-specific testing pitfalls
- Read the project's documented threat model before filing (rejected advisory)
- Reuse beats novelty: aligning with the upstream CI conventions made the
  reuse conversation easy

## 10. Conclusion and future work

- Enforcing CSP, coverage for SAML/LDAP paths, authenticated ZAP scans,
  mutation testing of the security suite

## References

- OWASP WSTG v4.2, OWASP Secure Headers Project, Kimai documentation,
  tool documentation (Semgrep, Trivy, ZAP, Dependency-Check, SonarQube),
  the two GitHub Security Advisories

## Appendix

- A: workflow file (`devsecops.yml`)
- B: Semgrep ruleset
- C: deployment configs (compose, nginx)
- D: redacted collaboration evidence
- E: assignment checklist (CHECKLIST.md)

---

### AODA / accessibility checklist for the PDF

- [ ] tagged PDF with proper heading structure (use the Springer styles)
- [ ] real text (no screenshots of code - paste code as text, monospace font)
- [ ] sufficient color contrast; no meaning conveyed by color alone
- [ ] alt text on every figure/diagram
- [ ] table headers marked; reading order verified
- [ ] document language set; title metadata filled
