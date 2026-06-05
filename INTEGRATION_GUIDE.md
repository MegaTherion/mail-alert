# Mail Alert System - Integration Guide

Guía de integración para sistemas externos que deseen enviar alertas.

## 🔗 Integración con Sistemas Externos

El endpoint POST /api/mail-alert está abierto para recibir alertas de cualquier sistema que conozca el `ALERT_SECRET`.

### Endpoint

```
POST http://tu-dominio.com/api/mail-alert
```

### Autenticación

Campo `secret` en el body JSON debe coincidir con `ALERT_SECRET` en backend `.env`.

```env
ALERT_SECRET=tu_clave_secreta_de_32_caracteres_minimo
```

### Request Format

```json
{
  "secret": "tu_clave_secreta_de_32_caracteres_minimo",
  "rule": "nombre_de_la_regla_que_se_disparo",
  "priority": "high|emergency",
  "from": "email@origin.com",
  "subject": "Asunto de la alerta",
  "snippet": "Texto corto descriptivo del evento",
  "timestamp": "2026-01-15T10:30:00Z"
}
```

### Campos Requeridos

- **secret** (string): Clave de autenticación
- **rule** (string): Identificador de la regla disparada
  - Ej: "github_push_main", "deployment_failed", "cpu_alert"
- **priority** (string): `high` o `emergency`
  - `emergency`: fullscreen alert + alarma
  - `high`: notificación normal
- **from** (string): Dirección/origen del alerta
  - Ej: "noreply@github.com", "alerts@datadog.com"
- **subject** (string): Asunto corto (max 255 chars)
- **snippet** (string): Descripción detallada del evento
- **timestamp** (string): ISO 8601 format: `YYYY-MM-DDTHH:mm:ssZ`

### Response

**Success (201):**
```json
{
  "success": true,
  "alert_id": 1
}
```

**Error - Invalid Secret (401):**
```json
{"error": "Invalid secret"}
```

**Error - Validation Failed (422):**
```json
{
  "error": "Validation failed",
  "details": {
    "priority": ["The priority field must be one of: high, emergency"]
  }
}
```

## 📝 Ejemplos de Integración

### 1. GitHub - Push a main

**Sistema**: GitHub Actions / Webhooks

```bash
#!/bin/bash

ALERT_URL="http://tu-dominio.com/api/mail-alert"
ALERT_SECRET="tu_clave_secreta"
REPO="$GITHUB_REPOSITORY"
BRANCH="$GITHUB_REF#refs/heads/}"

curl -X POST "$ALERT_URL" \
  -H "Content-Type: application/json" \
  -d "{
    \"secret\": \"$ALERT_SECRET\",
    \"rule\": \"github_push_main\",
    \"priority\": \"high\",
    \"from\": \"github.com\",
    \"subject\": \"Push to main: $REPO\",
    \"snippet\": \"Branch: $BRANCH\nCommit: ${GITHUB_SHA:0:7}\nAuthor: $GITHUB_ACTOR\",
    \"timestamp\": \"$(date -u +'%Y-%m-%dT%H:%M:%SZ')\"
  }"
```

### 2. Datadog - Alert

**Sistema**: Datadog Monitor

Webhook URL:
```
http://tu-dominio.com/api/mail-alert
```

Webhook payload (custom):
```json
{
  "secret": "tu_clave_secreta",
  "rule": "datadog_alert",
  "priority": "emergency",
  "from": "alerts@datadog.com",
  "subject": "Datadog Alert: {{alert.title}}",
  "snippet": "Status: {{alert.status}}\nMetric: {{alert.metric}}\nTime: {{alert.last_updated}}",
  "timestamp": "2026-01-15T10:30:00Z"
}
```

### 3. PagerDuty - Incident

**Sistema**: PagerDuty Webhook Integration

```python
import requests
import json
from datetime import datetime

def send_pagerduty_alert(incident):
    url = "http://tu-dominio.com/api/mail-alert"
    
    payload = {
        "secret": "tu_clave_secreta",
        "rule": "pagerduty_incident",
        "priority": "emergency" if incident["urgency"] == "high" else "high",
        "from": "alerts@pagerduty.com",
        "subject": f"PagerDuty: {incident['title']}",
        "snippet": f"Service: {incident['service']}\nStatus: {incident['status']}\nAssignee: {incident['assignee']}",
        "timestamp": datetime.utcnow().strftime("%Y-%m-%dT%H:%M:%SZ")
    }
    
    response = requests.post(url, json=payload)
    return response.json()
```

### 4. Custom Script - Any Source

**Node.js:**

```javascript
const axios = require('axios');

async function sendAlert(alertData) {
  try {
    const response = await axios.post(
      'http://tu-dominio.com/api/mail-alert',
      {
        secret: process.env.ALERT_SECRET,
        rule: alertData.rule,
        priority: alertData.priority || 'high',
        from: alertData.from,
        subject: alertData.subject,
        snippet: alertData.snippet,
        timestamp: new Date().toISOString()
      }
    );
    
    console.log('Alert sent:', response.data);
    return response.data;
  } catch (error) {
    console.error('Error sending alert:', error.response?.data || error.message);
    throw error;
  }
}

// Uso
sendAlert({
  rule: 'my_app_error',
  priority: 'emergency',
  from: 'myapp@company.com',
  subject: 'Database Connection Failed',
  snippet: 'Unable to connect to database. Max retries exceeded.'
});
```

**Python:**

```python
import requests
import json
import os
from datetime import datetime

def send_alert(rule, priority, from_addr, subject, snippet):
    url = os.getenv('ALERT_URL', 'http://localhost:8000/api/mail-alert')
    secret = os.getenv('ALERT_SECRET')
    
    payload = {
        'secret': secret,
        'rule': rule,
        'priority': priority,
        'from': from_addr,
        'subject': subject,
        'snippet': snippet,
        'timestamp': datetime.utcnow().isoformat() + 'Z'
    }
    
    response = requests.post(url, json=payload)
    
    if response.status_code in [200, 201]:
        return response.json()
    else:
        raise Exception(f"Alert failed: {response.status_code} - {response.text}")

# Uso
try:
    result = send_alert(
        rule='python_app_alert',
        priority='high',
        from_addr='myapp@example.com',
        subject='Backup completed',
        snippet='Database backup finished successfully at 02:00 UTC'
    )
    print(f"Alert sent with ID: {result['alert_id']}")
except Exception as e:
    print(f"Error: {e}")
```

**cURL:**

```bash
#!/bin/bash

# Variables
API_URL="http://tu-dominio.com/api/mail-alert"
ALERT_SECRET="tu_clave_secreta"
RULE="my_rule"
PRIORITY="high"
FROM="myapp@company.com"
SUBJECT="My Alert Subject"
SNIPPET="Alert details and context"
TIMESTAMP=$(date -u +'%Y-%m-%dT%H:%M:%SZ')

# Enviar alert
curl -X POST "$API_URL" \
  -H "Content-Type: application/json" \
  -d "{
    \"secret\": \"$ALERT_SECRET\",
    \"rule\": \"$RULE\",
    \"priority\": \"$PRIORITY\",
    \"from\": \"$FROM\",
    \"subject\": \"$SUBJECT\",
    \"snippet\": \"$SNIPPET\",
    \"timestamp\": \"$TIMESTAMP\"
  }"
```

### 5. Grafana - Alert

**Sistema**: Grafana Alert Notification Channel

Contact Point configuration:
```yaml
Type: Webhook
URL: http://tu-dominio.com/api/mail-alert
HTTP Method: POST

Custom Headers:
  Content-Type: application/json

Body Template:
{
  "secret": "tu_clave_secreta",
  "rule": "{{ .GroupLabels.alertname }}",
  "priority": "{{ if eq .Status \"firing\" }}emergency{{ else }}high{{ end }}",
  "from": "grafana@monitoring.com",
  "subject": "{{ .GroupLabels.alertname }}: {{ .Status }}",
  "snippet": "{{ range .Alerts }}Rule: {{ .Labels.rule_name }}\nInstance: {{ .Labels.instance }}\nValue: {{ .Values.B0 }}\n{{ end }}",
  "timestamp": "{{ now.Format \"2006-01-02T15:04:05Z07:00\" }}"
}
```

### 6. Zabbix - Integration

**Sistema**: Zabbix Webhook

```javascript
// Zabbix Webhook Script
var request = new HttpRequest();
var url = '{$ALERT_URL}'; // Macro: http://tu-dominio.com/api/mail-alert

var payload = JSON.stringify({
    'secret': '{$ALERT_SECRET}',
    'rule': '{TRIGGER.NAME}',
    'priority': ({TRIGGER.SEVERITY} >= 4) ? 'emergency' : 'high',
    'from': 'zabbix@monitoring.com',
    'subject': '{TRIGGER.NAME}: {TRIGGER.STATUS}',
    'snippet': 'Host: {HOST.NAME}\nProblem: {TRIGGER.DESCRIPTION}\nTime: {EVENT.TIME}',
    'timestamp': new Date().toISOString()
});

request.addHeader('Content-Type: application/json');
var response = request.post(url, payload);
```

### 7. Jenkins - Build Failure

**Sistema**: Jenkins Pipeline

```groovy
pipeline {
    post {
        failure {
            script {
                def alertUrl = 'http://tu-dominio.com/api/mail-alert'
                def alertSecret = credentials('alert-secret')
                
                def payload = [
                    'secret': alertSecret,
                    'rule': 'jenkins_build_failure',
                    'priority': 'high',
                    'from': "jenkins@${env.JENKINS_URL}",
                    'subject': "Build Failed: ${env.JOB_NAME} #${env.BUILD_NUMBER}",
                    'snippet': """
                    Project: ${env.JOB_NAME}
                    Build: ${env.BUILD_NUMBER}
                    Status: FAILED
                    Duration: ${currentBuild.durationString}
                    """.stripIndent(),
                    'timestamp': new Date().format("yyyy-MM-dd'T'HH:mm:ss'Z'")
                ]
                
                httpRequest(
                    url: alertUrl,
                    httpMode: 'POST',
                    contentType: 'APPLICATION_JSON',
                    requestBody: new groovy.json.JsonOutput().toJson(payload)
                )
            }
        }
    }
}
```

### 8. Custom Monitoring App

**Sistema**: App Node.js de monitoreo

```javascript
// monitoring-app.js
const express = require('express');
const axios = require('axios');

const ALERT_API = process.env.ALERT_URL;
const ALERT_SECRET = process.env.ALERT_SECRET;

// Cuando detecta problema
async function notifyAlert(event) {
  const alert = {
    secret: ALERT_SECRET,
    rule: event.type,
    priority: event.severity === 'critical' ? 'emergency' : 'high',
    from: 'monitoring@mycompany.com',
    subject: `${event.type}: ${event.service}`,
    snippet: `
Service: ${event.service}
Status: ${event.status}
Error: ${event.error}
Time: ${event.timestamp}
    `.trim(),
    timestamp: new Date().toISOString()
  };
  
  try {
    const response = await axios.post(ALERT_API, alert);
    console.log(`Alert sent: ${response.data.alert_id}`);
  } catch (error) {
    console.error('Failed to send alert:', error.message);
  }
}

// Uso
notifyAlert({
  type: 'high_cpu_usage',
  service: 'api-server-01',
  severity: 'critical',
  status: 'CPU > 95%',
  error: 'High CPU usage detected',
  timestamp: new Date().toISOString()
});
```

## 🔒 Seguridad en Integración

### Recomendaciones

1. **Guardar Secret en Variables de Entorno**
   ```bash
   export ALERT_SECRET="tu_clave_secreta"
   ```

2. **Usar HTTPS en Producción**
   ```
   https://tu-dominio.com/api/mail-alert
   ```

3. **Validar Certificados SSL**
   - No desactivar verificación en producción
   - Usar certs válidos de autoridad confiable

4. **Rotación de Secrets**
   - Cambiar `ALERT_SECRET` periódicamente
   - Actualizar en todos los sistemas integradores

5. **Rate Limiting** (Futuro)
   - Actualmente sin límite de requests
   - Implementar si es necesario

6. **Auditoría**
   - Revisar logs de alertas: `tail -f backend/storage/logs/laravel.log`
   - Ver qué sistemas enviaron qué alertas

## 📊 Monitoreo de Integración

### Ver alertas recibidas

```bash
curl http://tu-dominio.com/api/alerts \
  -H "Authorization: Bearer tu_clave_secreta"
```

### Analizar por origen (from_address)

```bash
# Contar alertas por origen
php artisan tinker
>>> App\Models\MailAlert::groupBy('from_address')->selectRaw('from_address, count(*) as total')->get()
```

### Alertas fallidas

```bash
# Alertas que no se enviaron a FCM (sent_at = null)
>>> App\Models\MailAlert::whereNull('sent_at')->count()
```

## 🔧 Troubleshooting de Integraciones

### Error: "Invalid secret"
- Verificar que `ALERT_SECRET` es exactamente igual
- Revisar caracteres especiales o espacios

### Error: "Validation failed"
- Revisar que todos los campos requeridos están presentes
- Validar formato ISO 8601 del timestamp
- Priority solo: "high" o "emergency"

### Alertas no llegan a la app
- Verificar que FCM está configurado correctamente
- Revisar logs: `tail -f backend/storage/logs/laravel.log | grep FCM`
- Verificar que app está subscrita al topic

### Timeout en requests
- Aumentar timeout en cliente (default: 10 segundos)
- Revisar conectividad de red
- Verificar que backend no está sobrecargado

## 📈 Casos de Uso Comunes

| Sistema | Rule | Priority | From | Frecuencia |
|---------|------|----------|------|-----------|
| GitHub | `github_push_main` | high | github.com | Por push |
| Datadog | `datadog_alert` | emergency | alerts@datadog.com | Variable |
| PagerDuty | `pagerduty_incident` | emergency | alerts@pagerduty.com | Variable |
| Jenkins | `jenkins_build_failure` | high | jenkins@company.com | Por build fallido |
| Grafana | `grafana_alert` | emergency | grafana@monitoring.com | Variable |
| Custom App | `custom_event` | high | myapp@company.com | Custom |

## 📞 Support

Para problemas de integración:
1. Revisar [TESTING.md](./TESTING.md)
2. Revisar logs backend
3. Verificar respuesta de API
4. Revisar logs en origen (GitHub, Datadog, etc)

¡Feliz integrando! 🚀
