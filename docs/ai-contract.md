# AI contract (in-process PHP)

No separate microservice. JSON endpoints under `/api/v1/ai/*`.

## Endpoints

### POST `/api/v1/ai/chat/start`
Body: `mode=registration|concierge`  
Returns: `{ session_token, messages[], complete }`

### POST `/api/v1/ai/chat/message`
Body: `session_token`, `message`  
Returns: `{ session_token, messages[], complete, slots?, handoff? }`

### POST `/api/v1/ai/ask`
Body: `question`  
Returns: `{ answer, grounded, provider, degraded }`

### GET `/api/v1/ai/health`
Returns: `{ providers: { claude, groq, gemini } }`

### POST `/api/v1/ai/match/{challengeId}/recompute`
Auth required. Persists ranked matches.

## Providers

Failover order: Claude → Groq → Gemini. Timeouts via `AI_TIMEOUT`. Requests logged to `ai_request_logs`. Secrets never logged.

## Guardrails

- RAG answers only from `knowledge_chunks` (+ FAQ seed).
- Challenge/solution text treated as untrusted data (prompt-injection filters).
- Em-dashes stripped from model output.
- If all providers down: chat continues with slot-filling/rules; matching uses rule-based scores; FAQ falls back to top chunk text.
