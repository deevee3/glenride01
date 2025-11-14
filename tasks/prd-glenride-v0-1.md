# Glenride v0.1 – Scenario Stress Tester & Event Parser

## 1. Introduction / Overview
Glenride v0.1 delivers a lightweight scenario stress-testing tool for supply-chain planners. It converts uploaded network data and unstructured operational text into structured disruption events, runs predefined scenarios, and surfaces impacted flows with suggested mitigations. The goal is to demonstrate rapid insight generation from messy operational inputs while establishing the foundation for multi-tenant conditional compute.

## 2. Goals
- Support 1–2 design partners in loading real network data and running at least four scenarios each within the first 90 days.
- Provide three predefined disruption scenarios (port closure, lead time inflation, labor capacity loss) with meaningful impact reporting.
- Parse at least one unstructured text channel into structured event data with ≥70% perceived accuracy.
- Track LLM usage and route requests through a cost-aware conditional compute layer.

## 3. User Stories
- As a supply-chain planner, I want to upload CSV files describing my network so that I can model disruption scenarios without custom IT work.
- As a supply-chain planner, I want to paste or upload operational text so that Glenride detects disruption events automatically.
- As a supply-chain planner, I want to run predefined what-if scenarios so that I can quickly see which flows and orders are at risk.
- As a supply-chain planner, I want to receive suggested mitigations so that I can act on disruptions without starting from scratch.
- As an operations lead, I want to monitor scenario results and event logs so that I can share insights with colleagues and improve decision-making.

## 4. Functional Requirements
1. The system must allow authenticated users to upload network node data via CSV with required columns (`node_id`, `name`, `type`, `capacity`).
2. The system must allow authenticated users to upload network flow data via CSV with required columns (`edge_id`, `origin_node_id`, `destination_node_id`, `avg_lead_time_days`, `volume`), and optional columns (`lead_time_std_days`, `cost_per_unit`).
3. The system must validate uploads end-to-end, aggregate errors (missing columns, invalid rows), and present a summary without persisting partial data.
4. The system must store uploaded nodes and edges scoped by the user’s tenant.
5. The system must allow users to paste text or upload `.txt`/PDF files and extract plain text for downstream parsing.
6. The system must route extracted text through an LLM-based parser that outputs structured events (`event_type`, `location`, `severity`, `start_date?`, `confidence`) and logs raw vs. parsed payloads.
7. The system must save parsed events with tenant scoping and display them in chronological order, including parse confidence and raw text reference.
8. The system must provide actions from events to pre-populate scenario parameters according to event type.
9. The system must implement predefined scenario types:
   1. Port closure (sets node capacity to zero for a duration parameter).
   2. Lead time inflation (applies a percentage multiplier to one or more edges).
   3. Labor capacity loss (reduces node capacity by a percentage).
10. The system must execute scenarios against the stored network model, compute impacted flows (eta change, congestion) and store results per run.
11. The system must generate basic metrics per scenario (number and percentage of flows impacted, aggregate lead time change, optional cost delta).
12. The system must provide simple recommendations by identifying alternate nodes of the same type with spare capacity and acceptable cost variance.
13. The system must expose scenario results in the UI with summaries, detailed flow tables, and links back to related events.
14. The system must capture LLM usage metadata (model, tokens, estimated cost, selection reason) for each parse invocation.
15. The system must scope all data access by tenant and ensure each user only accesses their tenant’s records.
16. The system should optionally support a Slack/Teams bot entry point using the same scenario APIs when time permits.

## 5. Non-Goals (Out of Scope)
- Full digital twin or advanced optimization (multi-echelon inventory, queueing models).
- Multi-agent or competitor behavior simulation.
- Comprehensive analytics dashboards beyond scenario summaries.
- Federated learning, homomorphic encryption, or complex privacy guarantees beyond basic tenant isolation.
- Chatbot integrations beyond basic slash commands (if implemented).

## 6. Design Considerations
- Follow the existing Glenride design system and Inertia React component patterns.
- Provide clear error summaries on uploads and parsing failures.
- Use skeleton loading states for deferred/async props in Inertia pages when applicable.

## 7. Technical Considerations
- Laravel modular monolith: services for ingestion, parsing, scenario engine housed within `app/Services` (or equivalent) for future extraction.
- Store tenant relationships using a `tenants` table and `tenant_id` foreign keys on domain tables; users belong to exactly one tenant in v0.1.
- Use an in-app PDF parsing library (e.g., `smalot/pdfparser`); external services optional later.
- Integrate with OpenAI (GPT-4 class models) via an abstraction layer to enable future provider swaps.
- Track LLM usage in a dedicated table for cost observability.
- Aim for scenario runtimes under 10 seconds for networks up to ~100 nodes / ~500 edges; allow graceful degradation beyond that.

## 8. Success Metrics
- ≥1 design partner completes four or more scenario runs using real data within 90 days.
- Median time from CSV upload to first scenario result ≤ 5 minutes for small networks.
- Event parsing achieves ≥70% perceived accuracy according to partner feedback.
- Scenario runs maintain estimated LLM cost below $0.25 per run on average.
- Users report at least one actionable decision derived from the tool within the first engagement cycle.

## 9. Open Questions
- Confirmation of final LLM model tier (GPT-4o vs GPT-4.1) once cost benchmarks are tested on partner data.
- Detailed reroute heuristics (geographic filters, cost ceilings) to refine recommendations after initial partner feedback.
- Priority and timing for Slack/Teams bot integration relative to core workflow stability.
