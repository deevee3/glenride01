Below is a lean but complete PRD for your 90‑day sprint, scoped for a **solo founder vibe-coding the stack**. It focuses on the smallest coherent product that still delivers real value and sets you up for the larger Glenride vision.

---

# Glenride v0.1 – Scenario Stress Tester & Event Parser  
**Product Requirements Document (PRD)**  
Horizon: First 90 days

---

## 1. Product Summary

Glenride v0.1 is a web + chat-based tool that lets a small, overstretched supply chain team:

1. Upload a simple representation of their network (nodes + flows).  
2. Upload or paste unstructured ops text (emails, PDFs converted to text).  
3. Automatically extract disruption “events” from text.  
4. Run a few predefined “what-if” scenarios on their network.  
5. See which flows/orders are impacted and a small set of suggested mitigations.

It is **not** a full-blown digital twin; it is a **usable stress-test tool** that demonstrates:

- Value: “We can see the impact of disruptions quickly.”  
- Data flywheel: “Our messy emails/docs become structured signals.”  
- Architecture: You have a basic conditional compute pattern and multi-tenant isolation in place.

---

## 2. Objectives & Success Criteria

### 2.1. Objectives (90 days)

1. Deliver a working v0.1 app that at least 1–2 design partners can use with real (possibly de-identified) data.
2. Support at least 3 basic disruption scenarios and show meaningful impact metrics.
3. Parse at least one unstructured channel (emails or copied text from PDFs) into structured events.
4. Implement a basic conditional compute pattern so you’re not locked into expensive LLM usage.

### 2.2. Success Metrics (Foundational, not strict KPIs)

By Day 90:

- 1–2 design partners:
  - Have uploaded real network data.
  - Have run ≥4 stress-test scenarios each.
  - Report at least one decision or insight they wouldn’t have had easily otherwise.
- System-level:
  - Time from CSV upload → first scenario result ≤ 5 minutes (for small networks).
  - Text parsing: ≥70% of relevant events correctly extracted (subjective but workable by partner feedback).
  - LLM cost per scenario run: observable and roughly bounded (e.g., <$0.25 per scenario for v0.1 internal targets).

---

## 3. Target Users & Use Cases

### 3.1. Primary User

**Role:** Supply chain planner / logistics lead / operations manager at a mid-sized company (brand or 3PL).

**Context:**

- Manages a network of 5–50 nodes (plants, DCs, ports).
- Works heavily in Excel, email, and a TMS/WMS (but not sophisticated simulation).
- Understaffed: no dedicated data science or OR team.

### 3.2. Core Use Cases v0.1

1. **Port Closure Scenario**  
   - “What happens if Port X is unavailable for 30 days?”

2. **Lead Time Inflation Scenario**  
   - “What happens if lead times on this lane increase by 50%?”

3. **Labor Capacity Loss Scenario**  
   - “What happens if warehouse Y effectively loses 20% throughput (labor shortfall)?”

4. **Event-to-Scenario Prompting**  
   - Paste an email about congestion at Port X → tool suggests:  
     “This looks like a lead time inflation event on Port X. Run a scenario?”

---

## 4. Scope for v0.1 (Must / Should / Won’t)

### 4.1. Must-Haves

- **Data Ingestion**
  - CSV/Excel upload for:
    - Nodes (ID, type, location, capacity).
    - Flows (origin, destination, average lead time, volume, cost).
  - Simple, single-tenant-ish multi-tenant support:
    - Tenant ID concept baked into tables.
- **Unstructured Text Input**
  - Web interface to:
    - Paste text (from emails / docs).
    - Upload .txt or simple PDF (extracted via lib → text).
- **Event Parsing**
  - Minimal LLM pipeline that:
    - Takes raw text.
    - Extracts structured events with fields:
      - `event_type` (e.g., `port_congestion`, `strike`, `weather_delay`)
      - `location` (e.g., port or node name)
      - `severity` (low/med/high or numeric)
      - `start_date` (optional)
      - `confidence` (0–1)
    - Logs raw vs parsed for debugging.
- **Scenario Engine**
  - Network representation:
    - Nodes with capacity.
    - Edges with lead time and cost.
  - Scenario types:
    - Port closure → capacity = 0 for a node.
    - Lead time inflation → LT *= (1 + alpha) for a chosen edge or set of edges.
    - Labor capacity loss → capacity *= (1 − beta) on chosen node.
  - Outputs:
    - Which flows are impacted (by node/edge).
    - Increase in expected lead times (per flow).
    - Simple metrics:
      - % flows impacted.
      - Weighted cost impact (optional simple approximation).
- **Recommendations (Simple)**
  - For each impacted flow:
    - If alternative node exists (e.g., nearby node or alternate route via a neighbor) and has spare capacity:
      - Suggest rerouting.
    - Else:
      - Flag as “manual attention needed.”
- **UI**
  - Web UI with:
    - Data Upload page.
    - Scenarios page:
      - Choose scenario type + parameters.
      - Display results: cards + tables.
    - Events page:
      - List parsed events.
      - Option to “Run scenario from event” (pre-populate scenario form).
- **Slack/Teams Bot (Optional but Preferred)**
  - Basic commands:
    - `/glenride_scenario PORT_CLOSURE port_name`
    - `/glenride_events` → list recent parsed events.
- **Conditional Compute (MVP)**
  - Simple internal router:
    - For short/simple texts → use cheaper/smaller LLM or lighter prompt.
    - For long/ambiguous texts → use bigger model.
  - Logging of:
    - Which model used.
    - Token usage and estimated cost.

### 4.2. Should-Haves (Stretch if Time Allows)

- More flexible scenario definition:
  - Combine multiple events (closure + LT inflation).
- Visualizations:
  - Basic network graph with highlighted impacted nodes/edges.
- Proactive suggestions:
  - When new event parsed: show a banner “We suggest running a scenario on X.”

### 4.3. Won’t-Haves (v0.1)

- Full digital twin (no detailed queuing/labor shifts, no multi-echelon inventory optimization).
- Multi-agent simulation (competitor/carrier/customs behavior).
- Full federated learning or homomorphic encryption implementation (beyond design-level and simple anonymization).
- Haptics/wearables integration.
- Robotics/automation integrations.

---

## 5. Detailed Functional Requirements

### 5.1. Data Model

#### Entities

1. **Tenant**
   - `tenant_id`
   - `name`

2. **Node**
   - `node_id`
   - `tenant_id`
   - `name`
   - `type` (port, DC, plant, etc.)
   - `capacity` (units per time window; use simple “units/day”)

3. **Edge (Flow Template)**
   - `edge_id`
   - `tenant_id`
   - `origin_node_id`
   - `destination_node_id`
   - `avg_lead_time_days`
   - `lead_time_std_days` (optional)
   - `volume` (e.g., units/day or shipments/day)
   - `cost_per_unit` (optional)

4. **Event**
   - `event_id`
   - `tenant_id`
   - `raw_text` (original snippet)
   - `event_type`
   - `location` (string, later mapped to node)
   - `severity` (scale: 1–3 or low/med/high)
   - `start_date` (nullable)
   - `confidence`
   - `created_at`

5. **Scenario**
   - `scenario_id`
   - `tenant_id`
   - `scenario_type` (port_closure, lead_time_inflation, labor_capacity_loss)
   - `parameters` (JSON: node_id, edges, alpha/beta, duration)
   - `created_by`
   - `created_at`

6. **Scenario Result**
   - `scenario_id` (FK)
   - `results` (JSON):
     - `flows_impacted`: list of edge_ids + metrics
     - `service_level_change` (basic metric)
     - `cost_delta_estimate` (optional)
     - `recommendations`: list of suggested reroutes or flags

---

### 5.2. Ingestion & Parsing Flow

1. **Upload structured data**
   - User uploads nodes CSV and flows CSV.
   - Backend:
     - Validates required columns.
     - Inserts/updates into Node and Edge tables.

2. **Upload/paste unstructured text**
   - User pastes text into a textarea or uploads .txt / PDF.
   - Backend:
     - For PDF: extract text.
     - Sends text to `parser-service` via API.

3. **Parser Service**
   - Determines whether to use cheap or big model (conditional compute):
     - `if len(text) < N && heuristics == simple` → small model.
     - else → large model.
   - Prompt LLM:
     - Ask for structured JSON output of events (possibly multiple per text).
   - Post-process:
     - Validate JSON structure.
     - Map location string to known node names if possible (fuzzy match).
   - Store events in Event table.

4. **Event UI**
   - Show list of events (most recent first).
   - Clicking an event:
     - Opens a modal with parsed info and “Run Scenario” button.
     - Pre-populate scenario creation form based on `event_type` + `location`:
       - e.g., `port_congestion` at node → lead_time_inflation scenario.

---

### 5.3. Scenario Simulation Flow

1. **User creates a scenario**
   - From Scenarios page or from an event.
   - Select:
     - Type.
     - Node/edge(s).
     - Severity (% change, total closure, etc.).
   - Click “Run.”

2. **Backend (sim-service)**

For each scenario:

- Load relevant nodes/edges for tenant.
- Apply modifications:
  - Port closure:
    - Target node capacity = 0.
  - Lead time inflation:
    - `avg_lead_time_days *= (1 + alpha)` for selected edges.
  - Labor capacity loss:
    - Node capacity *= (1 - beta).
- Compute impacts:
  - Simple treatment:
    - If node capacity < current volume → mark node as congested; factor in extra delay proportional to overload.
    - If lead time increases:
      - For each flow: recompute ETA = old ETA * (1 + alpha).
  - Identify impacted flows:
    - Where ETA increase > threshold or node is congested.
- Generate metrics:
  - `num_flows_impacted`, `percent_flows_impacted`, basic aggregated lead-time increase.

3. **Recommendations**
   - For each impacted flow:
     - Find alternative nodes/edges:
       - Example: other destination nodes of same type, within some distance threshold (initially just “other DCs in same country”).
     - If alternative exists and capacity not overloaded:
       - Suggest rerouting a portion of volume.
- Store results in Scenario Result table.

4. **Results UI**
   - Summary:
     - “X of Y flows impacted.”
     - Estimated average lead-time change.
   - Detailed table:
     - Flow: origin → destination.
     - Before vs after ETA.
     - Recommended action: reroute to Node Z / manual review.
   - (If time) small bar chart of impacted vs non-impacted flows.

---

### 5.4. Slack/Teams Bot (MVP)

- Slash commands:
  - `/glenride_scenario port_closure port_name`
    - Bot calls scenario API with pre-defined severity (total closure).
    - Returns a short textual summary + link to web UI.
  - `/glenride_events`
    - Returns list of the last N events (type, location, severity).

---

### 5.5. Conditional Compute

For v0.1:

- Route LLM calls based on text length & simple heuristics:
  - **Cheap Path**:
    - Short text (< 500 chars).
    - Single obvious event type (e.g., contains “delay”, “congestion”).
  - **Expensive Path**:
    - Long text or ambiguous language.
- Implementation:
  - A small `router()` function that:
    - Picks model name.
    - Adds extra instructions in prompt for complex cases.
- Logging:
  - Persist per call:
    - `model_used`, `input_tokens`, `output_tokens`, `estimated_cost`.

---

## 6. Non-Functional Requirements

### 6.1. Performance

- For small networks (up to ~100 nodes, ~500 edges):
  - Scenario run time: ≤ 10 seconds.
- For text parsing:
  - End-to-end parse latency: ≤ 10 seconds per document (LLM latency dominated).

### 6.2. Security & Privacy (v0.1)

- Basic tenant isolation:
  - All queries include `tenant_id`.
  - No cross-tenant queries.
- Minimal PII handling:
  - Assume data is operational (locations, SKUs, orders).  
  - If any PII appears (names, emails), store only as needed and consider masking in events.
- Use HTTPS everywhere.

### 6.3. Reliability

- If parsing fails:
  - Store raw text + error.
  - Show in UI as “Parse failed – click to retry.”
- If scenario fails:
  - Store error and show human-readable message.

---

## 7. Tech Stack (Proposed, Flexible)

Given solo dev and speed:

- Backend: Python (FastAPI) or Node.js (Express) – whichever you vibe with.
- DB: Postgres (Supabase / RDS / Neon, etc.).
- Frontend:  
  - React (Next.js) or a simple SPA—keep it as lightweight as you can.
- LLM Access:  
  - Hosted API (OpenAI / Anthropic) for parser.
- Infra:
  - Single cloud (e.g., Render, Fly.io, Railway, or AWS Lightsail) for fast iteration.
- Slack Bot:
  - Bolt SDK (Node) or simple webhook integration.

---

## 8. Milestones & Checkpoints

- **Day ~15:**  
  - Can upload CSVs and store network.
  - Can paste text → see parsed events (raw JSON) in dev.

- **Day ~30:**  
  - Internal CLI/API: run scenario on sample data.
  - Simple web UI: upload data, view events, run scenario, and see results for test tenant.

- **Day ~45:**  
  - First design partner’s data uploaded.
  - They run first scenarios with your guidance.

- **Day ~60:**  
  - Slack/Teams bot integrated.
  - Design partner can independently run scenarios.

- **Day ~90:**  
  - Improved simulation + recommendations.
  - Some proactive suggestions in UI based on parsed events.
  - 1–2 partners using product, with concrete anecdotal wins.

---

If you’d like, I can next:

- Turn this PRD into a **task breakdown / Kanban board** (epics → user stories → tasks) you can paste into Linear/Jira/Notion.